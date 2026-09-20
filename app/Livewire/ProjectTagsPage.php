<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectTagsPage extends Component
{
    public Project $project;

    public ?int $editingTagId = null;

    public string $title = '';

    public string $color = '#000000';

    public string $editTitle = '';

    public string $editColor = '#000000';

    public function mount(Project $project): void
    {
        $this->authorize('getProjectTags', $project);

        $this->project = $project;
    }

    public function createTag(): void
    {
        $this->authorize('edit', $this->project);
        $this->authorize('create', [Tag::class, $this->project]);

        $validated = $this->validate([
            'title' => 'required|string|min:6|max:50',
            'color' => 'required|string|regex:/^#[0-9a-f]{6}$/',
        ]);

        $tag = new Tag;
        $tag->title = $validated['title'];
        $tag->color = intval(substr($validated['color'], 1), 16);

        $this->project->tags()->save($tag);

        $this->reset('title', 'color');
    }

    public function editTag(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->editingTagId = $tag->id;
        $this->editTitle = $tag->title;
        $this->editColor = $tag->color;
    }

    public function saveTag(): void
    {
        $tag = Tag::findOrFail($this->editingTagId);

        $this->authorize('edit', $tag->project);
        $this->authorize('update', $tag);

        $validated = $this->validate([
            'editTitle' => 'required|string|min:6|max:50',
            'editColor' => 'required|string|regex:/^#[0-9a-f]{6}$/',
        ]);

        $tag->title = $validated['editTitle'];
        $tag->color = intval(substr($validated['editColor'], 1), 16);
        $tag->save();

        $this->reset('editingTagId');
    }

    public function deleteTag(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->authorize('edit', $tag->project);
        $this->authorize('delete', $tag);

        $tag->delete();
    }

    public function render(): View
    {
        $searchTerm = request()->query('q') ?? '';

        $tags = $this->project->tags();

        if ($searchTerm !== '') {
            $tags = $tags->where('title', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm).'%');
        }

        return view('livewire.project-tags-page', [
            'tags' => $tags->cursorPaginate(10)->withQueryString(),
        ])->layout('layouts.project', ['project' => $this->project])
            ->title($this->project->name);
    }
}
