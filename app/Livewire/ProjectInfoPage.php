<?php

namespace App\Livewire;

use App\Models\Project;
use App\Notifications\ProjectArchived;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectInfoPage extends Component
{
    public Project $project;

    public bool $editing = false;

    public string $name = '';

    public string $description = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function edit(): void
    {
        $this->authorize('update', $this->project);

        $this->name = $this->project->name;
        $this->description = $this->project->description['raw'];
        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->project);

        $validated = $this->validate([
            'name' => 'string|min:6|max:255',
            'description' => 'string|min:6|max:512',
        ]);

        $this->project->name = $validated['name'];
        $this->project->description = $validated['description'];
        $this->project->save();

        $this->editing = false;
    }

    public function archive(): void
    {
        $this->authorize('archive', $this->project);

        $this->project->archived = true;
        $this->project->save();

        foreach ($this->project->users as $user) {
            $user->notify(new ProjectArchived($this->project));
        }
    }

    public function unarchive(): void
    {
        $this->authorize('unarchive', $this->project);

        $this->project->archived = false;
        $this->project->save();
    }

    public function deleteProject(): void
    {
        $this->authorize('delete', $this->project);

        $this->project->delete();

        $this->redirectRoute('project.list', navigate: true);
    }

    public function leave(): void
    {
        $this->authorize('leaveProject', $this->project);

        $this->project->users()->detach(request()->user());

        $this->redirectRoute('project.list', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.project-info-page')
            ->layout('layouts.project', ['project' => $this->project])
            ->title($this->project->name);
    }
}
