<?php

namespace App\Listeners;

use App\Events\ThreadCommentCreated;
use App\Notifications\ThreadCommented;

class SendThreadCommented
{
    public function handle(ThreadCommentCreated $event)
    {
        $event->comment->thread->author->notify(new ThreadCommented($event->comment));
    }
}
