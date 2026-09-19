<?php

namespace App\Listeners;

use App\Events\TaskCommentCreated;
use App\Notifications\TaskCommented;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskCommented implements ShouldQueue
{
    public function handle(TaskCommentCreated $event)
    {
        foreach ($event->comment->task->assignees as $assignee) {
            $assignee->notify(new TaskCommented($event->comment));
        }

    }
}
