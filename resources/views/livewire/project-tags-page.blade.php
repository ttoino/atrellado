<section class="flex-column p-3 d-flex container gap-3 narrow">
    <div class="hstack justify-content-between gap-3">
        <h2>Tags</h2>

        <form method="GET" action="{{ route('project.tags', ['project' => $project]) }}" class="input-group"
            role="search" style="max-width: 360px">
            <input class="form-control" name="q" type="search" placeholder="Search tags" aria-label="Search"
                value="{{ request()->query('q', '') }}">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @if ($tags->isEmpty())
        <div class="vstack align-items-center justify-content-center h-100">
            <p class="display-5">No tags match the search term!</p>
        </div>
    @else
        <ul class="list-group shadow-sm">
            @foreach ($tags as $item)
                <li wire:key="tag-{{ $item->id }}"
                    class="list-group-item list-group-item-action position-relative d-flex flex-row align-items-center gap-2">

                    <div style="background-color: rgb(var(--tag-color)); --tag-color: {{ $item->rgb_color }}"
                        class="p-2 rounded-circle">
                    </div>

                    @if ($editingTagId === $item->id)
                        <form wire:submit="saveTag" class="input-group flex-fill">
                            <input class="form-control form-control-color" type="color" wire:model="editColor"
                                required style="max-width: 38px">
                            <input type="text" minlength="6" maxlength="50" required wire:model="editTitle"
                                placeholder="Tag title" class="form-control">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>
                    @else
                        <span class="fw-bold flex-fill">
                            {{ $item->title }}
                        </span>
                    @endif

                    @if (!$item->project->archived)
                        @can('update', $item)
                            <button wire:click="editTag({{ $item->id }})" class="btn btn-outline-primary"
                                style="z-index: 5" aria-label="Edit tag">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endcan
                    @endif

                    @can('delete', $item)
                        <button wire:click="deleteTag({{ $item->id }})" wire:confirm="Delete this tag?"
                            class="btn btn-outline-danger" style="z-index: 5" aria-label="Delete tag"><i
                                class="bi bi-trash"></i></button>
                    @endcan
                </li>
            @endforeach
        </ul>

        {{ $tags->links() }}
    @endif

    @if (!$project->archived)
        @can('create', [App\Models\Tag::class, $project])
            <form wire:submit="createTag" class="input-group">
                <input class="form-control form-control-color" type="color" wire:model="color" required
                    style="max-width: 38px">
                <input type="text" minlength="6" maxlength="50" required wire:model="title"
                    placeholder="Create a new tag" class="form-control @error('title') is-invalid @enderror">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-plus"></i>
                </button>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </form>
        @endcan
    @endif
</section>
