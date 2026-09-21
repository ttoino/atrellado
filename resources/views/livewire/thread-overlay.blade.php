<div>
    <article id="thread" @class(['editable', 'editing' => $editing])>
        <header class="offcanvas-header">
            <h2 class="offcanvas-title">
                {{ $thread->title }}
            </h2>
            <a href="{{ route('project.forum', ['project' => $thread->project]) }}" wire:navigate class="btn-close"
                aria-label="Close"></a>
        </header>

        <a href="{{ route('user.profile', ['user' => $thread->author]) }}" role="button" style="z-index: 100"
            class="hstack gap-2">
            <img width="40" height="40" alt="Profile picture" src="{{ asset($thread->author?->profile_pic) }}"
                class="rounded-circle">
            <div class="vstack">
                <span>{{ $thread->author?->name }}</span>
                <time datetime="{{ $thread->creation_date ? $thread->creation_date['iso'] : null }}">
                    {{ $thread->creation_date ? $thread->creation_date['datetime'] : null }}
                </time>
            </div>
        </a>

        <div class="content">
            {!! $thread->content['formatted'] !!}
        </div>

        <span @class(['d-none' => $thread->edit_date == null, 'fst-italic'])>Edited <time
                datetime="{{ $thread->edit_date ? $thread->edit_date['iso'] : null }}">
                {{ $thread->edit_date ? $thread->edit_date['long_diff'] : null }}</time>
        </span>

        @can('edit', $thread->project)
            @can('update', $thread)
                <div class="hstack gap-2">
                    <button wire:click="edit" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button wire:click="deleteThread" wire:confirm="Delete this thread?" class="btn btn-outline-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            @endcan

            @if ($editing)
                <form wire:submit="save" class="edit needs-validation" novalidate>
                    <div class="form-floating">
                        <input aria-describedby="thread-title-feedback"
                            class="form-control @error('editTitle') is-invalid @enderror" type="text"
                            wire:model="editTitle" id="thread-title" placeholder="" minlength=6 maxlength=50 required>
                        <label for="thread-title" class="form-label">Title</label>
                        <div class="invalid-feedback" id="thread-title-feedback">
                            @error('editTitle')
                                {{ $message }}
                            @else
                                Invalid title
                            @enderror
                        </div>
                    </div>

                    <div class="form-floating">
                        <textarea placeholder="" class="form-control auto-resize @error('editContent') is-invalid @enderror"
                            id="thread-content" aria-describedby="thread-content-feedback" wire:model="editContent"
                            minlength=6 maxlength=512 required></textarea>
                        <a href="https://www.markdownguide.org/basic-syntax/"><i class="bi bi-markdown"></i> Markdown is
                            supported</a>
                        <label for="thread-content" class="form-label">Content</label>
                        <div class="invalid-feedback" id="thread-content-feedback">
                            @error('editContent')
                                {{ $message }}
                            @else
                                Invalid content
                            @enderror
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Save changes</button>
                </form>
            @endif
        @endcan
    </article>

    <ul id="thread-comments">
        @foreach ($comments as $comment)
            <li wire:key="thread-comment-{{ $comment->id }}" @class(['thread-comment', 'editable', 'editing' => $editingCommentId === $comment->id])>
                <a href="{{ route('user.profile', ['user' => $comment->author]) }}" role="button" style="z-index: 100"
                    class="hstack gap-2">
                    <img width="40" height="40" alt="Profile picture"
                        src="{{ asset($comment->author?->profile_pic) }}" class="rounded-circle">
                    <div class="vstack">
                        <span>{{ $comment->author?->name }}</span>
                        <time datetime="{{ $comment->creation_date ? $comment->creation_date['iso'] : null }}">
                            {{ $comment->creation_date ? $comment->creation_date['datetime'] : null }}
                        </time>
                    </div>
                </a>

                <div class="content">
                    {!! $comment->content['formatted'] !!}
                </div>

                <span @class(['d-none' => $comment->edit_date == null, 'fst-italic'])>Edited <time
                        datetime="{{ $comment->edit_date ? $comment->edit_date['iso'] : null }}">
                        {{ $comment->edit_date ? $comment->edit_date['long_diff'] : null }}</time>
                </span>

                @can('edit', $thread->project)
                    @can('update', $comment)
                        <div class="hstack gap-2">
                            <button wire:click="editComment({{ $comment->id }})" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button wire:click="deleteComment({{ $comment->id }})" wire:confirm="Delete this comment?"
                                class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    @endcan

                    @if ($editingCommentId === $comment->id)
                        <form wire:submit="saveComment" class="edit needs-validation" novalidate>
                            <div class="form-floating">
                                <textarea placeholder="" class="form-control auto-resize" id="thread-comment-content"
                                    aria-describedby="thread-comment-content-feedback" wire:model="editCommentContent"
                                    minlength=6 maxlength=512 required></textarea>
                                <a href="https://www.markdownguide.org/basic-syntax/"><i class="bi bi-markdown"></i>
                                    Markdown is supported</a>
                                <label for="thread-comment-content" class="form-label">Content</label>
                                <div class="invalid-feedback" id="thread-comment-content-feedback">
                                    Invalid content
                                </div>
                            </div>

                            <button class="btn btn-primary" type="submit">Save changes</button>
                        </form>
                    @endif
                @endcan
            </li>
        @endforeach
    </ul>

    @if ($hasMore)
        <button wire:click="loadMore" class="btn btn-primary mx-auto mb-3">
            Load more comments
        </button>
    @endif

    @can('edit', $thread->project)
        <form wire:submit="addComment" class="input-group">
            <textarea wire:model="newComment" id="thread-comment-new" placeholder="New comment"
                class="auto-resize form-control"></textarea>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-send"></i>
            </button>
        </form>
    @endcan
</div>
