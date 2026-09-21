<?php

namespace App\Livewire;

use App\Events\BoardChanged;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectBoardPage extends Component
{
    public Project $project;

    public ?Task $task = null;

    public string $newGroupName = '';

    /** @var array<int, string> */
    public array $groupNames = [];

    /** @var array<int, string> */
    public array $newTaskNames = [];

    public bool $creating = false;

    public string $name = '';

    public string $description = '';

    public ?int $taskGroupId = null;

    /** @var array<int> */
    public array $tags = [];

    /** @var array<int> */
    public array $assignees = [];

    public function mount(Project $project, ?Task $task = null): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
        $this->task = $task;
    }

    public function createGroup(): void
    {
        $this->authorize('edit', $this->project);
        $this->authorize('create', [TaskGroup::class, $this->project]);

        $validated = $this->validate([
            'newGroupName' => 'required|string|min:4|max:255',
        ]);

        $group = new TaskGroup;
        $group->name = $validated['newGroupName'];
        $group->description = '';
        $group->project_id = $this->project->id;
        $group->save();

        $this->reset('newGroupName');
        $this->changed();
    }

    public function renameGroup(int $id): void
    {
        $group = TaskGroup::findOrFail($id);

        $this->authorize('edit', $group->project);
        $this->authorize('update', $group);

        $validated = $this->validate([
            "groupNames.$id" => 'required|string|min:4|max:255',
        ]);

        $group->name = $validated['groupNames'][$id];
        $group->save();

        $this->changed();
    }

    public function deleteGroup(int $id): void
    {
        $group = TaskGroup::findOrFail($id);

        $this->authorize('edit', $group->project);
        $this->authorize('delete', $group);

        abort_if($group->tasks()->exists(), 403);

        $group->delete();

        $this->changed();
    }

    public function createTask(int $groupId): void
    {
        $group = TaskGroup::findOrFail($groupId);

        $this->authorize('edit', $this->project);
        $this->authorize('create', [Task::class, $group]);

        $validated = $this->validate([
            "newTaskNames.$groupId" => 'required|string|min:4|max:255',
        ]);

        $this->saveTask($group, $validated['newTaskNames'][$groupId], '');

        unset($this->newTaskNames[$groupId]);
    }

    public function createFullTask(): void
    {
        $group = TaskGroup::findOrFail($this->taskGroupId);

        $this->authorize('edit', $this->project);
        $this->authorize('create', [Task::class, $group]);

        $validated = $this->validate([
            'name' => 'required|string|min:4|max:255',
            'description' => 'nullable|string|min:6|max:512',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'integer',
            'assignees' => 'nullable|array|max:5',
            'assignees.*' => 'integer',
        ]);

        $task = $this->saveTask($group, $validated['name'], $validated['description'] ?? '');
        $this->syncRelations($task, $validated['tags'] ?? [], $validated['assignees'] ?? []);

        $this->reset('name', 'description', 'tags', 'assignees', 'creating');
    }

    #[On('task-moved')]
    public function repositionTask(int $id, int $group, int $position): void
    {
        $task = Task::findOrFail($id);

        $this->authorize('edit', $task->project);
        $this->authorize('edit', $task);

        $task->task_group_id = $group;
        $task->position = $position;
        $task->save();

        $this->changed();
    }

    #[On('group-moved')]
    public function repositionGroup(int $id, int $position): void
    {
        $group = TaskGroup::findOrFail($id);

        $this->authorize('edit', $group->project);
        $this->authorize('update', $group);

        $group->position = $position;
        $group->save();

        $this->changed();
    }

    #[On('echo-private:project.{project.id},.board.changed')]
    #[On('echo-private:project.{project.id},.task-comment.created')]
    public function onBoardChanged(): void
    {
        // Re-render: groups/tasks re-query and morph reconciles positions.
    }

    public function render(): View
    {
        $groups = $this->project->taskGroups()
            ->orderBy('position')
            ->with(['tasks' => fn ($query) => $query->orderBy('position')->with('tags', 'assignees')->withCount('comments')])
            ->get();

        // Livewire needs backing values for the per-group wire:model arrays.
        foreach ($groups as $group) {
            $this->groupNames[$group->id] ??= $group->name;
        }

        return view('livewire.project-board-page', [
            'groups' => $groups,
        ])->extends('layouts.project', ['project' => $this->project])
            ->section('project-content')
            ->title($this->task === null ? $this->project->name : $this->task->name);
    }

    private function saveTask(TaskGroup $group, string $name, string $description): Task
    {
        $task = new Task;
        $task->name = $name;
        $task->description = $description;
        $task->task_group_id = $group->id;
        $task->creator_id = request()->user()->id;
        $task->save();

        $this->changed();

        return $task->fresh();
    }

    /** @param  array<int>  $tagIds  @param  array<int>  $assigneeIds */
    private function syncRelations(Task $task, array $tagIds, array $assigneeIds): void
    {
        foreach ($tagIds as $tagId) {
            $task->attachTag(Tag::findOrFail($tagId));
        }

        foreach ($assigneeIds as $assigneeId) {
            $assignee = User::findOrFail($assigneeId);
            $task->attachAssignee($assignee);
            $assignee->notify(new TaskAssigned($task, request()->user()));
        }
    }

    private function changed(): void
    {
        BoardChanged::dispatch($this->project);
    }
}
