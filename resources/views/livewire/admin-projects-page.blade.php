<div class="flex-column p-3 container gap-3">
    <div class="hstack justify-content-between gap-3">
        @include('partials.admin.nav')

        <form method="GET" action="{{ route('admin.projects') }}" class="input-group" role="search"
            style="max-width: 360px">
            <input class="form-control" name="q" type="search" placeholder="Search projects" aria-label="Search"
                value="{{ request()->query('q', '') }}">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @if ($projects->isEmpty())
        <div class="vstack align-items-center justify-content-center h-100">
            <p>No project match the search term!</p>
        </div>
    @else
        <ul class="list-group shadow-sm">
            @foreach ($projects as $item)
                <li wire:key="project-{{ $item->id }}"
                    class="list-group-item list-group-item-action position-relative d-flex flex-row align-items-center gap-2">
                    <div class="vstack flex-fill">
                        <a href="{{ route('project', ['project' => $item]) }}" class="stretched-link fw-bold">
                            {{ $item->name }}
                        </a>
                        <span>Coordinator:
                            {{ $item->coordinator->name }}</span>
                    </div>

                    @if ($item->archived)
                        <span class="text-warning">Archived</span>
                    @endif

                    <a href="{{ route('admin.reports.project', ['project' => $item]) }}"
                        class="btn btn-outline-secondary" style="z-index: 5">Reports
                        ({{ $item->reports->count() }})</a>

                    @can('toggleFavorite', $item)
                        <button wire:click="toggleFavorite({{ $item->id }})" class="btn btn-outline-primary"
                            style="z-index: 5" aria-label="Toggle favorite">
                            <i @class([
                                'bi',
                                'bi-heart' => !$item->pivot?->is_favorite,
                                'bi-heart-fill' => $item->pivot?->is_favorite,
                            ])></i>
                        </button>
                    @endcan

                    @can('delete', $item)
                        <button wire:click="deleteProject({{ $item->id }})" wire:confirm="Delete this project?"
                            class="btn btn-outline-danger" style="z-index: 5" aria-label="Delete project"><i
                                class="bi bi-trash"></i></button>
                    @endcan
                </li>
            @endforeach
        </ul>

        {{ $projects->links() }}
    @endif
</div>
