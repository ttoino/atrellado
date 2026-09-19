<?php

namespace App\Listeners;

use App\Events\TaskCommentCreated;
use App\Notifications\TaskCommented;

class SendTaskCommented
{
    public function handle(TaskCommentCreated $event)
    {
        foreach ($event->comment->task->assignees as $assignee) {
            $assignee->notify(new TaskCommented($event->comment));
        }

    }
}
