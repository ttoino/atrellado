<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ThreadCreated extends ThreadEvent implements ShouldBroadcast
{
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.'.$this->thread->project_id);
    }

    public function broadcastAs(): string
    {
        return 'thread.created';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->thread->id,
            'project_id' => $this->thread->project_id,
        ];
    }
}
