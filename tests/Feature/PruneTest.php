<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

it('prunes notifications older than ninety days', function () {
    $user = makeUser();

    $old = Notification::create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [],
        'created_at' => now()->subDays(100),
    ]);

    $recent = Notification::create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [],
        'created_at' => now(),
    ]);

    $this->artisan('model:prune')->assertSuccessful();

    expect(Notification::find($old->id))->toBeNull()
        ->and(Notification::find($recent->id))->not->toBeNull();
});
