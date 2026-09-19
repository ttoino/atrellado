<?php

namespace App\Listeners;

use App\Events\ProjectDeleted;
use App\Notifications\ProjectDeleted as ProjectDeletedNotif;

class SendProjectDeleted
{
    public function handle(ProjectDeleted $event)
    {
        foreach ($event->project->users as $user) {
            $user->notify(new ProjectDeletedNotif($event->project));
        }
    }
}
