<?php

namespace App\Listeners;

use App\Events\ProjectDeleted;
use App\Notifications\ProjectDeleted as ProjectDeletedNotif;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendProjectDeleted implements ShouldQueue
{
    public function handle(ProjectDeleted $event)
    {
        foreach ($event->project->users as $user) {
            $user->notify(new ProjectDeletedNotif($event->project));
        }
    }
}
