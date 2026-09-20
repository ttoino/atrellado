<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationsPage extends Component
{
    public function markAsRead(string $id): void
    {
        $notification = Notification::findOrFail($id);

        $this->authorize('markRead', $notification);

        $notification->markAsRead();
    }

    public function render(): View
    {
        return view('livewire.notifications-page', [
            'notifications' => request()->user()->unreadNotifications()->cursorPaginate(10),
        ]);
    }
}
