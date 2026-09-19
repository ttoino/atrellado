<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;

abstract class Notification extends BaseNotification
{
    use Queueable;

    abstract public function toArray($notifiable);

    public function toDatabase($notifiable)
    {
        return [
            'json' => $this->toArray($notifiable),
            'type' => get_class($this),
            'notifiable_id' => $notifiable->id,
        ];
    }
}
