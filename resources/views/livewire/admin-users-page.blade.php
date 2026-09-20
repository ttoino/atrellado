<div class="flex-column p-3 container gap-3">
    <div class="hstack justify-content-between gap-3">
        @include('partials.admin.nav')

        <form method="GET" action="{{ route('admin.users') }}" class="input-group" role="search"
            style="max-width: 360px">
            <input class="form-control" name="q" type="search" placeholder="Search users" aria-label="Search"
                value="{{ request()->query('q', '') }}">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @if ($users->isEmpty())
        <div class="vstack align-items-center justify-content-center h-100">
            <p>No users match the search term!</p>
        </div>
    @else
        <ul class="list-group shadow-sm">
            @foreach ($users as $item)
                <li wire:key="user-{{ $item->id }}"
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

                    <a href="{{ route('admin.reports.user', ['user' => $item]) }}" class="btn btn-outline-secondary"
                        style="z-index: 5">Reports
                        ({{ $item->reports_count }})</a>

                    @can('block', $item)
                        <button wire:click="blockUser({{ $item->id }})" class="btn btn-outline-secondary"
                            style="z-index: 5">Block</button>
                    @endcan

                    @can('unblock', $item)
                        <button wire:click="unblockUser({{ $item->id }})" class="btn btn-outline-secondary"
                            style="z-index: 5">Unblock</button>
                    @endcan

                    @can('delete', $item)
                        <button wire:click="deleteUser({{ $item->id }})" wire:confirm="Delete this user?"
                            class="btn btn-outline-danger" style="z-index: 5" aria-label="Delete user"><i
                                class="bi bi-trash"></i></button>
                    @endcan
                </li>
            @endforeach
        </ul>

        {{ $users->links() }}
    @endif
</div>
