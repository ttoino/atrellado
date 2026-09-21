<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Thread;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectForumPage extends Component
{
    public Project $project;

    public ?Thread $thread = null;

    public bool $creating = false;

    public string $title = '';

    public string $content = '';

    public function mount(Project $project, ?Thread $thread = null): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
        $this->thread = $thread;
    }

    public function createThread(): void
    {
        $this->authorize('edit', $this->project);
        $this->authorize('create', [Thread::class, $this->project]);

        $validated = $this->validate([
            'title' => 'required|string|min:6|max:50',
            'content' => 'required|string|min:6|max:512',
        ]);

        $thread = new Thread;
        $thread->title = $validated['title'];
        $thread->content = $validated['content'];
        $thread->author_id = request()->user()->id;

        $this->project->threads()->save($thread);

        $this->redirectRoute('project.thread', ['project' => $this->project, 'thread' => $thread], navigate: true);
    }

    #[On('echo-private:project.{project.id},.thread.created')]
    public function onThreadCreated(): void
    {
        // Re-render: the thread list re-queries and picks up the new thread.
    }

    public function render(): View
    {
        return view('livewire.project-forum-page', [
            'threads' => $this->project->threads()->with('author')->withCount('comments')->get(),
        ])->extends('layouts.project', ['project' => $this->project])
            ->section('project-content')
            ->title($this->thread === null ? $this->project->name : $this->thread->title);
    }
}
