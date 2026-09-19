<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;

class CustomDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $model = $notification->toDatabase($notifiable);

        return (new NotificationModel($model))->save();
    }
}
