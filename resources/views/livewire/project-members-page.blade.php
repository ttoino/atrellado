<section class="flex-column p-3 d-flex container gap-3 narrow">
    <div class="hstack justify-content-between gap-3">
        <h2>Members</h2>

        <form method="GET" action="{{ route('project.members', ['project' => $project]) }}" class="input-group"
            role="search" style="max-width: 360px">
            <input class="form-control" name="q" type="search" placeholder="Search members" aria-label="Search"
                value="{{ request()->query('q', '') }}">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @if ($members->isEmpty())
        <div class="vstack align-items-center justify-content-center h-100">
            <p class="display-5">No members match the search term!</p>
        </div>
    @else
        <ul class="list-group shadow-sm">
            @foreach ($members as $item)
                <li wire:key="member-{{ $item->id }}"
                    class="list-group-item list-group-item-action position-relative d-flex flex-row align-items-center gap-2">

                    <img src="{{ asset($item->profile_pic) }}" alt="Profile picture" width="40" height="40"
                        class="rounded-circle">

                    <div class="vstack flex-fill align-self-center">
                        <a href="{{ route('user.profile', ['user' => $item]) }}" @class([
                            'stretched-link',
                            'fw-bold',
                            'underline' => Auth::user()?->id === $item->id,
                        ])>
                            {{ $item->name }}
                        </a>

                        @if ($item->is_admin)
                            <span class="text-success">Admin</span>
                        @endif

                        @if ($item->blocked)
                            <span class="text-danger">Blocked</span>
                        @endif
                    </div>

                    @can('report', $item)
                        <a href="{{ route('user.report', ['user' => $item]) }}" class="btn btn-outline-danger"
                            style="z-index: 5">
                            Report
                        </a>
                    @endcan

                    @can('removeUser', [$project, $item])
                        <button wire:click="removeUser({{ $item->id }})" wire:confirm="Remove this member?"
                            class="btn btn-outline-danger" style="z-index: 5" aria-label="Remove member"><i
                                class="bi bi-x-lg"></i></button>
                    @endcan

                    @can('setCoordinator', [$project, $item])
                        <button wire:click="setCoordinator({{ $item->id }})" class="btn btn-outline-danger"
                            style="z-index: 5">
                            Make coordinator
                        </button>
                    @endcan
                </li>
            @endforeach
        </ul>

        {{ $members->links() }}
    @endif

    @can('create', [App\Models\Tag::class, $project])
        <form wire:submit="invite" class="input-group">
            <input type="email" wire:model="email" minlength="6" maxlength="50" required
                placeholder="Invite a new user" class="form-control @error('email') is-invalid @enderror">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-plus"></i>
            </button>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </form>
    @endcan
</section>
