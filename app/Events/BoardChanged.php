<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by the board components after any task/group mutation so other
 * clients on the project channel re-render; the triggering client has
 * already re-rendered via its own Livewire response.
 */
class BoardChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Project $project) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.'.$this->project->id);
    }

    public function broadcastAs(): string
    {
        return 'board.changed';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->project->id,
        ];
    }
}
