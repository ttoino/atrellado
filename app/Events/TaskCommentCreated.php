<?php

namespace App\Events;

use App\Models\TaskComment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\DB;

class TaskCommentCreated extends TaskCommentEvent implements ShouldBroadcast
{
    protected int $projectId;

    public function __construct(TaskComment $comment)
    {
        parent::__construct($comment);

        // One join instead of hydrating Task (with its tags/creator/
        // assignees eager loads) and TaskGroup to reach the project id.
        $this->projectId = (int) DB::table('task')
            ->join('task_group', 'task.task_group_id', '=', 'task_group.id')
            ->where('task.id', $comment->task_id)
            ->value('task_group.project_id');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.'.$this->projectId);
    }

    public function broadcastAs(): string
    {
        return 'task-comment.created';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'project_id' => $this->projectId,
            'task_id' => $this->comment->task_id,
        ];
    }
}
