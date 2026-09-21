<section class="project-info editable">
    <header>
        <h2 class="h1">{{ $project->name }}</h2>
        <p>
            Created on
            <time datetime="{{ $project->creation_date['iso'] }}">
                {{ $project->creation_date['datetime'] }}
            </time>
        </p>
        <p>
            Last edited
            <time datetime="{{ $project->edit_date ? $project->edit_date['iso'] : '' }}">
                {{ $project->edit_date ? $project->edit_date['long_diff'] : 'never' }}
            </time>
        </p>
    </header>
    <div class="buttons">
        @can('update', $project)
            <button wire:click="edit" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i>
                Edit
            </button>
        @endcan

        @can('archive', $project)
            <button wire:click="archive" wire:confirm="Archive this project?" class="btn btn-outline-secondary">
                <i class="bi bi-archive"></i>
                Archive
            </button>
        @endcan

        @can('unarchive', $project)
            <button wire:click="unarchive" class="btn btn-outline-secondary">
                <i class="bi bi-archive"></i>
                Unarchive
            </button>
        @endcan

        @can('delete', $project)
            <button wire:click="deleteProject" wire:confirm="Delete this project? This cannot be undone."
                class="btn btn-outline-danger">
                <i class="bi bi-trash"></i>
                Delete
            </button>
        @endcan

        @can('leaveProject', $project)
            <button wire:click="leave" wire:confirm="Leave this project?" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i>
                Leave project
            </button>
        @endcan

        @can('report', $project)
            <a href="{{ route('project.report', ['project' => $project]) }}" class="btn btn-outline-danger">
                Report Project
            </a>
        @endcan
    </div>

    @if ($editing)
        <form wire:submit="save" class="edit needs-validation" novalidate>
            <div class="form-floating">
                <input placeholder="" class="form-control @error('name') is-invalid @enderror" id="name"
                    type="text" wire:model="name" minlength="6" maxlength="512" required autofocus>
                <label for="name" class="form-label">Name</label>
                <div class="invalid-feedback" id="name-feedback">
                    @error('name')
                        {{ $message }}
                    @else
                        Invalid name
                    @enderror
                </div>
            </div>

            <div class="form-floating">
                <textarea placeholder="" class="form-control auto-resize @error('description') is-invalid @enderror"
                    id="description" wire:model="description" minlength="6" maxlength="512" required></textarea>
                <label for="description" class="form-label">Description</label>
                <div class="invalid-feedback" id="description-feedback">
                    @error('description')
                        {{ $message }}
                    @else
                        Invalid description
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Save changes
            </button>
        </form>
    @else
        <div class="description">{!! $project->description['formatted'] !!}</div>
    @endif
</section>
