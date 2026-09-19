<?php

namespace App\Listeners;

use App\Events\ThreadCreated;
use App\Notifications\ThreadNew;

class SendThreadNew
{
    public function handle(ThreadCreated $event)
    {
        foreach ($event->thread->project->users as $user) {
            $user->notify(new ThreadNew($event->thread));
        }

    }
}
