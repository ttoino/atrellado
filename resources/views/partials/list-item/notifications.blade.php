<li data-notification-id="{{ $item->id }}"
    class="list-group-item list-group-item-action position-relative hstack gap-2">
    <div class="vstack flex-fill">
        @switch ($item->type)
            @case('App\Notifications\ProjectInvite')
                <a class="stretched-link" href={{ url($item->data['url']) }}>You've been
                    invited to join
                    <strong>{{ $item->data['project_name'] }}</strong>
                    <br> Click here
                    to join.</a>
            @break

            @case('App\Notifications\ProjectRemoved')
                <a class="stretched-link" href={{ route('project.list') }}>You were
                    removed from
                    <strong>{{ $item->data['project_name'] }}</strong>
                </a>
            @break

            @case('App\Notifications\ProjectArchived')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    <strong>{{ $item->data['project_name'] }}</strong>
                    has been archived.
                </a>
            @break

            @case('App\Notifications\ProjectDeleted')
                <span href={{ route('project.list') }}>
                    <strong>{{ $item->data['project_name'] }}</strong> has been deleted.
                </span>
            @break

            @case('App\Notifications\TaskAssigned')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    You've been assigned to
                    <strong>{{ $item->data['task_name'] }}</strong>
                    in
                    <strong>{{ $item->data['project_name'] }}</strong>
                </a>
            @break

            @case('App\Notifications\TaskCommented')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    There's a new comment on a task you're assigned to
                </a>
            @break

            @case('App\Notifications\TaskCompleted')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    A task you're assigned to has been completed
                </a>
            @break

            @case('App\Notifications\ThreadNew')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    A new thread has been opened in
                    <strong>{{ $item->data['project_name'] }}</strong>.
                </a>
            @break

            @case('App\Notifications\ThreadCommented')
                <a class="stretched-link" href={{ $item->data['url'] }}>
                    Theres a new comment on your thread
                </a>
            @break
        @endswitch
        <time datetime="{{ $item->created_at->toISOString() }}">
            {{ $item->created_at->diffForHumans(['aUnit' => true]) }}
        </time>
    </div>
    <button type="button" style="z-index: 100" class="read-notification-button btn btn-outline-primary">
        <i class="bi bi-check-lg"></i>
        Mark as read
    </button>
</li>
