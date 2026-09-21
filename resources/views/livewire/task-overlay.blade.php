<div>
    <article id="task" @class(['editable', 'editing' => $editing])>
        <header class="offcanvas-header">
            <h2 class="offcanvas-title">{{ $task->name }} <i @class(['bi', 'bi-check-lg', 'd-none' => !$task->completed])></i>
            </h2>
            <a href="{{ route('project.board', ['project' => $task->project]) }}" wire:navigate class="btn-close"
                aria-label="Close"></a>
        </header>

        <a href="{{ route('user.profile', ['user' => $task->creator]) }}" role="button" style="z-index: 100"
            class="hstack gap-2">
            <img width="40" height="40" alt="Profile picture" src="{{ asset($task->creator?->profile_pic) }}"
                class="rounded-circle">
            <div class="vstack">
                <span>{{ $task->creator?->name }}</span>
                <time datetime="{{ $task->creation_date ? $task->creation_date['iso'] : null }}">
                    {{ $task->creation_date ? $task->creation_date['datetime'] : null }}
                </time>
            </div>
        </a>

        <div class="content">
            {!! $task->description['formatted'] !!}
        </div>

        <span @class(['d-none' => $task->edit_date == null, 'fst-italic'])>Edited <time
                datetime="{{ $task->edit_date ? $task->edit_date['iso'] : null }}">
                {{ $task->edit_date ? $task->edit_date['long_diff'] : null }}</time>
        </span>

        <ul class="tags">
            @foreach ($task->tags as $tag)
                <li style="--tag-color: {{ $tag->rgb_color }}">
                    <a href="{{ route('project.tasks', ['project' => $task->project, 'q' => $tag->title]) }}">
                        {{ $tag->title }}
                    </a>
                </li>
            @endforeach
        </ul>

        <ul class="assignees">
            @foreach ($task->assignees as $assignee)
                <li>
                    <a href="{{ route('user.profile', ['user' => $assignee]) }}" data-bs-toggle="tooltip"
                        data-bs-title="{{ $assignee->name }}" data-bs-placement="bottom">
                        <img src="{{ asset($assignee->profile_pic) }}" alt="{{ $assignee->name }}" width="24" height="24"
                            class="rounded-circle">
                    </a>
                </li>
            @endforeach
        </ul>

        @can('edit', $task->project)
            <div class="hstack gap-2">
                @if ($task->completed)
                    <button wire:click="incomplete" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Mark as incomplete
                    </button>
                @else
                    <button wire:click="complete" class="btn btn-outline-secondary">
                        <i class="bi bi-check-lg"></i> Mark as completed
                    </button>
                @endif
                <button wire:click="edit" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button wire:click="deleteTask" wire:confirm="Delete this task?" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>

            @if ($editing)
                <form wire:submit="save" class="edit needs-validation" novalidate>
                    <div class="form-floating">
                        <input aria-describedby="task-name-feedback" placeholder=""
                            class="form-control @error('editName') is-invalid @enderror" type="text" wire:model="editName"
                            id="task-name" minlength=4 maxlength=255 required>
                        <label for="task-name" class="form-label">Name</label>
                        <div id="task-name-feedback" class="invalid-feedback">
                            @error('editName')
                                {{ $message }}
                            @else
                                Please enter a valid name.
                            @enderror
                        </div>
                    </div>

                    <div class="form-floating">
                        <textarea placeholder="" class="form-control auto-resize @error('editDescription') is-invalid @enderror"
                            aria-describedby="task-description-feedback" wire:model="editDescription" id="task-description"
                            minlength=6 maxlength=512></textarea>
                        <label for="task-description" class="form-label">Description</label>
                        <div id="task-description-feedback" class="invalid-feedback">
                            @error('editDescription')
                                {{ $message }}
                            @else
                                Please enter a valid description.
                            @enderror
                        </div>
                    </div>

                    <label class="d-flex flex-column gap-1">
                        Tags
                        <select class="form-select" aria-describedby="edit-task-tags-feedback" wire:model="editTags"
                            id="edit-task-tags" multiple>
                            @foreach ($task->project->tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array($tag->id, $editTags))>
                                    {{ $tag->title }}
                                </option>
                            @endforeach
                        </select>
                        <div id="edit-task-tags-feedback" class="invalid-feedback">
                            Please select a valid set of tags.
                        </div>
                    </label>

                    <label class="d-flex flex-column gap-1">
                        Assignees
                        <select class="form-select" aria-describedby="edit-task-assignees-feedback" wire:model="editAssignees"
                            id="edit-task-assignees" multiple>
                            @foreach ($task->project->users as $user)
                                <option value="{{ $user->id }}" @selected(in_array($user->id, $editAssignees))>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="edit-task-assignees-feedback" class="invalid-feedback">
                            Please select a valid set of assignees.
                        </div>
                    </label>

                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            @endif
        @endcan
    </article>

    <ul id="task-comments">
        @foreach ($comments as $comment)
            <li wire:key="task-comment-{{ $comment->id }}" @class(['task-comment', 'editable', 'editing' => $editingCommentId === $comment->id])>
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

                @can('edit', $task->project)
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
                                <textarea placeholder="" class="form-control auto-resize" id="task-comment-content"
                                    aria-describedby="task-comment-content-feedback" wire:model="editCommentContent"
                                    minlength=6 maxlength=512 required></textarea>
                                <a href="https://www.markdownguide.org/basic-syntax/"><i class="bi bi-markdown"></i>
                                    Markdown is supported</a>
                                <label for="task-comment-content" class="form-label">Content</label>
                                <div class="invalid-feedback" id="task-comment-content-feedback">
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

    @can('edit', $task->project)
        <form wire:submit="addComment" class="input-group">
            <textarea class="form-control auto-resize" wire:model="newComment" required placeholder="New comment"></textarea>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-send"></i>
            </button>
        </form>
    @endcan
</div>
