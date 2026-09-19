<?php

use App\Models\Notification;

it('prunes notifications older than ninety days', function () {
    $user = makeUser();

    $old = Notification::create([
        'type' => 'test',
        'notifiable_id' => $user->id,
        'json' => [],
        'creation_date' => now()->subDays(100),
    ]);

    $recent = Notification::create([
        'type' => 'test',
        'notifiable_id' => $user->id,
        'json' => [],
        'creation_date' => now(),
    ]);

    $this->artisan('model:prune')->assertSuccessful();

    expect(Notification::find($old->id))->toBeNull()
        ->and(Notification::find($recent->id))->not->toBeNull();
});
