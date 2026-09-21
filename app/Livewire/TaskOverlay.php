<?php

namespace App\Livewire;

use App\Events\BoardChanged;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCompleted;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskOverlay extends Component
{
    public Task $task;

    public int $commentCount = 10;

    public bool $editing = false;

    public string $editName = '';

    public string $editDescription = '';

    /** @var array<int> */
    public array $editTags = [];

    /** @var array<int> */
    public array $editAssignees = [];

    public ?int $editingCommentId = null;

    public string $editCommentContent = '';

    public string $newComment = '';

    public function mount(Task $task): void
    {
        $this->authorize('view', [$task, $task->project]);

        $this->task = $task;
    }

    public function loadMore(): void
    {
        $this->commentCount += 10;
    }

    public function edit(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('edit', $this->task);

        $this->editName = $this->task->name;
        $this->editDescription = $this->task->description['raw'];
        $this->editTags = $this->task->tags->pluck('id')->all();
        $this->editAssignees = $this->task->assignees->pluck('id')->all();
        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('edit', $this->task);

        $validated = $this->validate([
            'editName' => 'string|min:4|max:255',
            'editDescription' => 'nullable|string|min:6|max:512',
            'editTags' => 'nullable|array|max:5',
            'editTags.*' => 'integer',
            'editAssignees' => 'nullable|array|max:5',
            'editAssignees.*' => 'integer',
        ]);

        // Captured before the sync so only genuinely new assignees get notified.
        $previousAssigneeIds = $this->task->assignees->pluck('id')->all();

        $this->task->name = $validated['editName'];
        $this->task->description = $validated['editDescription'] ?? '';
        $this->task->save();

        $this->task->tags()->detach();
        $this->task->assignees()->detach();

        foreach ($validated['editTags'] ?? [] as $tagId) {
            $this->task->attachTag(Tag::findOrFail($tagId));
        }

        foreach ($validated['editAssignees'] ?? [] as $assigneeId) {
            $assignee = User::findOrFail($assigneeId);
            $this->task->attachAssignee($assignee);
            if (! in_array($assignee->id, $previousAssigneeIds, true)) {
                $assignee->notify(new TaskAssigned($this->task, request()->user()));
            }
        }

        $this->editing = false;
        $this->changed();
    }

    public function complete(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('completeTask', $this->task);

        $this->task->completed = true;
        $this->task->save();

        foreach ($this->task->assignees as $assignee) {
            $assignee->notify(new TaskCompleted($this->task));
        }

        $this->changed();
    }

    public function incomplete(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('incompleteTask', $this->task);

        $this->task->completed = false;
        $this->task->save();

        $this->changed();
    }

    public function deleteTask(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('delete', $this->task);

        $project = $this->task->project;

        $this->task->delete();

        BoardChanged::dispatch($project);

        $this->redirectRoute('project.board', ['project' => $project], navigate: true);
    }

    public function addComment(): void
    {
        $this->authorize('edit', $this->task->project);
        $this->authorize('create', [TaskComment::class, $this->task]);

        $validated = $this->validate([
            'newComment' => 'required|string|min:0|max:512',
        ]);

        $comment = new TaskComment;
        $comment->content = $validated['newComment'];
        $comment->author_id = request()->user()->id;
        $comment->task_id = $this->task->id;
        $comment->save();

        $this->reset('newComment');
    }

    public function editComment(int $commentId): void
    {
        $comment = TaskComment::findOrFail($commentId);

        $this->authorize('edit', $comment->task->project);
        $this->authorize('update', $comment);

        $this->editingCommentId = $comment->id;
        $this->editCommentContent = $comment->content['raw'];
    }

    public function saveComment(): void
    {
        $comment = TaskComment::findOrFail($this->editingCommentId);

        $this->authorize('edit', $comment->task->project);
        $this->authorize('update', $comment);

        $validated = $this->validate([
            'editCommentContent' => 'required|string|min:0|max:512',
        ]);

        $comment->content = $validated['editCommentContent'];
        $comment->save();

        $this->reset('editingCommentId', 'editCommentContent');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = TaskComment::findOrFail($commentId);

        $this->authorize('edit', $comment->task->project);
        $this->authorize('delete', $comment);

        $comment->delete();
    }

    #[On('echo-private:project.{task.taskGroup.project_id},.task-comment.created')]
    public function onTaskCommentCreated(array $payload): void
    {
        if (($payload['task_id'] ?? null) !== $this->task->id) {
            $this->skipRender();
        }
    }

    public function render(): View
    {
        $comments = $this->task->comments()->with('author')->take($this->commentCount)->get();

        return view('livewire.task-overlay', [
            'comments' => $comments,
            'hasMore' => $this->task->comments()->count() > $comments->count(),
        ]);
    }

    private function changed(): void
    {
        BoardChanged::dispatch($this->task->project);
    }
}
