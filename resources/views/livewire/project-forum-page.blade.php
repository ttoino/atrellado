<div class="d-flex flex-fill overflow-auto">
    <section class="forum-threads">
        @can('edit', $project)
            <header>
                <button wire:click="$set('creating', true)" @class(['btn', 'btn-primary'])>
                    <i class="bi bi-plus"></i>New Thread
                </button>
            </header>
        @endcan
        <ul>
            @foreach ($threads as $item)
                <li wire:key="thread-{{ $item->id }}" class="thread">
                    <a href="{{ route('user.profile', ['user' => $item->author]) }}" role="button" style="z-index: 100">
                        <img width="40" height="40" alt="Profile picture"
                            src="{{ asset($item->author?->profile_pic) }}" class="rounded-circle">
                    </a>

                    <div>
                        <div>
                            <a href="{{ route('project.thread', ['project' => $project, 'thread' => $item]) }}"
                                wire:navigate @class(['fw-bold', 'stretched-link'])>
                                {{ $item->title }}
                            </a>
                            <time datetime="{{ $item->creation_date ? $item->creation_date['iso'] : null }}">
                                {{ $item->creation_date ? $item->creation_date['diff'] : null }}
                            </time>
                        </div>
                        <div>
                            <a href="{{ route('user.profile', ['user' => $item->author]) }}" role="button"
                                style="z-index: 100">
                                {{ $item->author?->name }}
                            </a>
                            <span class="comments" data-comment-count="{{ $item->comments_count }}">
                                <i class="bi bi-reply"></i>
                            </span>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($thread)
        <aside id="thread-offcanvas" class="show offcanvas-md offcanvas-end">
            <livewire:thread-overlay :thread="$thread" />
        </aside>
    @endif

    @if ($creating)
        <aside id="new-thread-offcanvas" class="show">
            <header class="offcanvas-header">
                <h2 class="offcanvas-title h4" id="new-thread-offcanvas-title">
                    New thread
                </h2>
                <button type="button" class="btn-close" wire:click="$set('creating', false)" aria-label="Close"></button>
            </header>
            <form wire:submit="createThread" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input aria-describedby="new-thread-title-feedback"
                        class="form-control @error('title') is-invalid @enderror" type="text" wire:model="title"
                        id="new-thread-title" placeholder="" minlength=6 maxlength=50 required>
                    <label for="new-thread-title" class="form-label">Title</label>
                    <div class="invalid-feedback" id="new-thread-title-feedback">
                        @error('title')
                            {{ $message }}
                        @else
                            Invalid title
                        @enderror
                    </div>
                </div>

                <div class="form-floating">
                    <textarea placeholder="" class="form-control auto-resize @error('content') is-invalid @enderror"
                        id="new-thread-content" aria-describedby="new-thread-content-feedback" wire:model="content"
                        minlength=6 maxlength=512 required></textarea>
                    <a href="https://www.markdownguide.org/basic-syntax/"><i class="bi bi-markdown"></i> Markdown is
                        supported</a>
                    <label for="new-thread-content" class="form-label">Content</label>
                    <div class="invalid-feedback" id="new-thread-content-feedback">
                        @error('content')
                            {{ $message }}
                        @else
                            Invalid content
                        @enderror
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Create thread</button>
            </form>
        </aside>
    @endif
</div>
