<?php

namespace App\Events;

use App\Models\ThreadComment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\DB;

class ThreadCommentCreated extends ThreadCommentEvent implements ShouldBroadcast
{
    protected int $projectId;

    public function __construct(ThreadComment $comment)
    {
        parent::__construct($comment);

        // Single scalar query; loading the thread relation would hydrate
        // the model and its author eager load just to route the broadcast.
        $this->projectId = (int) DB::table('thread')
            ->where('id', $comment->thread_id)
            ->value('project_id');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.'.$this->projectId);
    }

    public function broadcastAs(): string
    {
        return 'thread-comment.created';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'project_id' => $this->projectId,
            'thread_id' => $this->comment->thread_id,
        ];
    }
}
