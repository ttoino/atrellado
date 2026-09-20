<div class="vstack gap-1 align-self-center">
    <ul class="tags">
        @foreach ($task->tags as $tag)
            <li style="--tag-color: {{ $tag->rgb_color }}">
                <a href="{{ route('project.tasks', ['project' => $project, 'q' => $tag->title]) }}">
                    {{ $tag->title }}
                </a>
            </li>
        @endforeach
    </ul>

    <a class="stretched-link"
        href="{{ route('project.task.info', ['project' => $project, 'task' => $task->id ?? 0]) }}">
        {{ $task->name }}
    </a>

    <div class="bottom-row">
        <ul class="assignees"
            data-length="{{ $task->assignees->count() }}">
            @foreach ($task->assignees as $assignee)
                <li>
                    <a href="{{ route('user.profile', ['user' => $assignee]) }}" data-bs-toggle="tooltip"
                        data-bs-title="{{ $assignee->name }}" data-bs-placement="bottom">
                        <img src="{{ asset($assignee->profile_pic) }}" alt="{{ $assignee->name }}" width="24"
                            height="24" class="rounded-circle">
                    </a>
                </li>
            @endforeach
        </ul>
        <span class="comments"
            data-comment-count="{{ $task->comments->count() }}">
            <i class="bi bi-reply"></i>
        </span>
    </div>
</div>

<i @class(['completed-check', 'd-none' => !$task->completed])></i>
