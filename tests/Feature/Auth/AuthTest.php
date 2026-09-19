<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

it('registers a new user with a hashed password', function () {
    $response = $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('logs in with valid credentials and rejects bad ones', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = makeUser();

    $this->actingAs($user)->get('/logout');

    $this->assertGuest();
});

it('emails a recovery link and resets the password through the broker', function () {
    Notification::fake();
    $user = makeUser();

    $this->post('/recover-password', ['email' => $user->email])
        ->assertSessionDoesntHaveErrors();

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    expect(Hash::check('new-password123', $user->fresh()->password))->toBeTrue();
});

it('verifies an email through a signed url', function () {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
