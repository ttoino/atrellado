<?php

namespace App\Listeners;

use App\Events\ThreadCommentCreated;
use App\Notifications\ThreadCommented;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendThreadCommented implements ShouldQueue
{
    public function handle(ThreadCommentCreated $event)
    {
        $event->comment->thread->author->notify(new ThreadCommented($event->comment));
    }
}
