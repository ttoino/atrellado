<?php

use App\Livewire\NotificationsPage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function makeNotification(User $user, array $data = []): Notification
{
    return Notification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\ProjectDeleted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['project_name' => 'Secret project', ...$data],
    ]);
}

it('requires authentication', function () {
    $this->get(route('notifications'))->assertRedirect(route('login'));
});

it('lists the user\'s unread notifications', function () {
    $user = makeUser();
    makeNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationsPage::class)
        ->assertSee('Secret project')
        ->assertSee('Mark as read');
});

it('shows an empty state when everything is read', function () {
    $user = makeUser();
    makeNotification($user)->markAsRead();

    Livewire::actingAs($user)
        ->test(NotificationsPage::class)
        ->assertSee("You don't have any notifications yet!", false);
});

it('marks a notification as read', function () {
    $user = makeUser();
    $notification = makeNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationsPage::class)
        ->call('markAsRead', $notification->id)
        ->assertDontSee('Secret project');

    expect($notification->fresh()->read())->toBeTrue();
});

it('forbids marking another user\'s notification as read', function () {
    $user = makeUser();
    $notification = makeNotification(makeUser());

    Livewire::actingAs($user)
        ->test(NotificationsPage::class)
        ->call('markAsRead', $notification->id)
        ->assertForbidden();
});
