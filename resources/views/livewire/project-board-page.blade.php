<div class="d-flex flex-fill overflow-auto">
    <section class="project-board" data-board>
        @foreach ($groups as $group)
            <div wire:key="group-{{ $group->id }}" class="task-group" data-task-group-id="{{ $group->id }}">
                <header>
                    @can('edit', $project)
                        <i class="grip group-grip" style="cursor: grab"></i>
                    @endcan

                    <form class="edit-task-group-form" wire:submit="renameGroup({{ $group->id }})">
                        <textarea wire:model="groupNames.{{ $group->id }}" class="auto-resize single-line" autocomplete="off"
                            name="name" minlength="4" required @cannot('edit', $project) disabled @endcannot>{{ $group->name }}</textarea>
                    </form>

                    @can('edit', $project)
                        <button type="button" wire:click="deleteGroup({{ $group->id }})" wire:confirm="Delete this group?"
                            @class(['delete-task-group', 'd-none' => $group->tasks->isNotEmpty()])><i class="bi bi-trash"></i></button>
                    @endcan
                </header>

                <ul>
                    @foreach ($group->tasks as $item)
                        <li wire:key="task-{{ $item->id }}" class="task" data-task-id="{{ $item->id }}">
                            @can('edit', $project)
                                <i class="grip" style="z-index: 50; cursor: grab"></i>
                            @endcan

                            <div class="vstack gap-1 align-self-center">
                                <ul class="tags">
                                    @foreach ($item->tags as $tag)
                                        <li style="--tag-color: {{ $tag->rgb_color }}">
                                            <a href="{{ route('project.tasks', ['project' => $project, 'q' => $tag->title]) }}">
                                                {{ $tag->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <a class="stretched-link" wire:navigate
                                    href="{{ route('project.task.info', ['project' => $project, 'task' => $item]) }}">
                                    {{ $item->name }}
                                </a>

                                <div class="bottom-row">
                                    <ul class="assignees" data-length="{{ $item->assignees->count() }}">
                                        @foreach ($item->assignees as $assignee)
                                            <li>
                                                <a href="{{ route('user.profile', ['user' => $assignee]) }}"
                                                    data-bs-toggle="tooltip" data-bs-title="{{ $assignee->name }}"
                                                    data-bs-placement="bottom">
                                                    <img src="{{ asset($assignee->profile_pic) }}" alt="{{ $assignee->name }}"
                                                        width="24" height="24" class="rounded-circle">
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <span class="comments" data-comment-count="{{ $item->comments_count }}">
                                        <i class="bi bi-reply"></i>
                                    </span>
                                </div>
                            </div>

                            <i @class(['completed-check', 'd-none' => !$item->completed])></i>
                        </li>
                    @endforeach
                </ul>

                @can('edit', $project)
                    <form class="input-group new-task-form" wire:submit="createTask({{ $group->id }})">
                        <textarea wire:model="newTaskNames.{{ $group->id }}" class="auto-resize single-line form-control"
                            autocomplete="off" placeholder="Create Task" name="name" minlength="4" required></textarea>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-plus"></i></button>
                    </form>
                @endcan
            </div>
        @endforeach

        @can('edit', $project)
            <div class="task-group">
                <form class="input-group needs-validation" id="new-task-group-form" wire:submit="createGroup" novalidate>
                    <textarea wire:model="newGroupName" class="auto-resize single-line form-control" autocomplete="off"
                        placeholder="Create Group" name="name" minlength="4" required></textarea>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-plus"></i></button>
                </form>
            </div>

            <a id="new-task-button" role="button" wire:click="$set('creating', true)">
                <i class="bi bi-plus"></i> Create task
            </a>
        @endcan
    </section>

    @if ($task)
        <aside id="task-offcanvas" class="show offcanvas">
            <livewire:task-overlay :task="$task" />
        </aside>
    @endif

    @if ($creating)
        <aside id="new-task-offcanvas" class="show">
            <header class="offcanvas-header">
                <h2 class="offcanvas-title h4" id="new-task-offcanvas-title">
                    New task
                </h2>
                <button type="button" class="btn-close" wire:click="$set('creating', false)" aria-label="Close"></button>
            </header>
            <form wire:submit="createFullTask" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input aria-describedby="new-task-name-feedback" placeholder=""
                        class="form-control @error('name') is-invalid @enderror" type="text" wire:model="name"
                        id="new-task-name" minlength=4 maxlength=255 required>
                    <label for="new-task-name" class="form-label">Name</label>
                    <div id="new-task-name-feedback" class="invalid-feedback">
                        @error('name')
                            {{ $message }}
                        @else
                            Please enter a valid name.
                        @enderror
                    </div>
                </div>

                <div class="form-floating">
                    <textarea placeholder="" class="form-control auto-resize @error('description') is-invalid @enderror"
                        aria-describedby="new-task-description-feedback" wire:model="description" id="new-task-description"
                        minlength=6 maxlength=512></textarea>
                    <label for="new-task-description" class="form-label">Description</label>
                    <div id="new-task-description-feedback" class="invalid-feedback">
                        @error('description')
                            {{ $message }}
                        @else
                            Please enter a valid description.
                        @enderror
                    </div>
                </div>

                <div class="form-floating">
                    <select @class(['form-select', 'is-invalid' => $errors->has('taskGroupId')])
                        aria-describedby="new-task-group-feedback" wire:model="taskGroupId" id="new-task-task-group" required>
                        <option value="">Choose a task group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <label for="new-task-task-group" class="form-label">Task Group</label>
                    <div id="new-task-group-feedback" class="invalid-feedback">
                        @error('taskGroupId')
                            {{ $message }}
                        @else
                            Please select a valid task group.
                        @enderror
                    </div>
                </div>

                <label class="d-flex flex-column gap-1">
                    Tags
                    <select class="form-select" aria-describedby="new-task-tags-feedback" wire:model="tags"
                        id="new-task-tags" multiple>
                        @foreach ($project->tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->title }}</option>
                        @endforeach
                    </select>
                    <div id="new-task-tags-feedback" class="invalid-feedback">
                        Please select a valid set of tags.
                    </div>
                </label>

                <label class="d-flex flex-column gap-1">
                    Assignees
                    <select class="form-select" aria-describedby="new-task-assignees-feedback" wire:model="assignees"
                        id="new-task-assignees" multiple>
                        @foreach ($project->users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div id="new-task-assignees-feedback" class="invalid-feedback">
                        Please select a valid set of assignees.
                    </div>
                </label>

                <button type="submit" class="btn btn-primary">Create task</button>
            </form>
        </aside>
    @endif
</div>
