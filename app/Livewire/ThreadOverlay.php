<?php

namespace App\Livewire;

use App\Models\Thread;
use App\Models\ThreadComment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ThreadOverlay extends Component
{
    public Thread $thread;

    public int $commentCount = 10;

    public bool $editing = false;

    public string $editTitle = '';

    public string $editContent = '';

    public ?int $editingCommentId = null;

    public string $editCommentContent = '';

    public string $newComment = '';

    public function mount(Thread $thread): void
    {
        $this->authorize('view', $thread);

        $this->thread = $thread;
    }

    public function loadMore(): void
    {
        $this->commentCount += 10;
    }

    public function edit(): void
    {
        $this->authorize('edit', $this->thread->project);
        $this->authorize('update', $this->thread);

        $this->editTitle = $this->thread->title;
        $this->editContent = $this->thread->content['raw'];
        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('edit', $this->thread->project);
        $this->authorize('update', $this->thread);

        $validated = $this->validate([
            'editTitle' => 'string|min:6|max:50',
            'editContent' => 'string|min:6|max:512',
        ]);

        $this->thread->title = $validated['editTitle'];
        $this->thread->content = $validated['editContent'];
        $this->thread->save();

        $this->editing = false;
    }

    public function deleteThread(): void
    {
        $this->authorize('edit', $this->thread->project);
        $this->authorize('delete', $this->thread);

        $this->thread->delete();

        $this->redirectRoute('project.forum', ['project' => $this->thread->project], navigate: true);
    }

    public function addComment(): void
    {
        $this->authorize('edit', $this->thread->project);
        $this->authorize('create', [ThreadComment::class, $this->thread]);

        $validated = $this->validate([
            'newComment' => 'required|string|min:0|max:512',
        ]);

        $comment = new ThreadComment;
        $comment->content = $validated['newComment'];
        $comment->author_id = request()->user()->id;
        $comment->thread_id = $this->thread->id;
        $comment->save();

        $this->reset('newComment');
    }

    public function editComment(int $commentId): void
    {
        $comment = ThreadComment::findOrFail($commentId);

        $this->authorize('edit', $comment->thread->project);
        $this->authorize('update', $comment);

        $this->editingCommentId = $comment->id;
        $this->editCommentContent = $comment->content['raw'];
    }

    public function saveComment(): void
    {
        $comment = ThreadComment::findOrFail($this->editingCommentId);

        $this->authorize('edit', $comment->thread->project);
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
        $comment = ThreadComment::findOrFail($commentId);

        $this->authorize('edit', $comment->thread->project);
        $this->authorize('delete', $comment);

        $comment->delete();
    }

    #[On('echo-private:project.{thread.project_id},.thread-comment.created')]
    public function onThreadCommentCreated(array $payload): void
    {
        // Only fresh comments on the open thread matter; the author's own
        // comment is already rendered by addComment.
        if (($payload['thread_id'] ?? null) !== $this->thread->id) {
            $this->skipRender();
        }
    }

    public function render(): View
    {
        $comments = $this->thread->comments()->with('author')->take($this->commentCount)->get();

        return view('livewire.thread-overlay', [
            'comments' => $comments,
            'hasMore' => $this->thread->comments()->count() > $comments->count(),
        ]);
    }
}
