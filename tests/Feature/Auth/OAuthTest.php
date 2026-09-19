<?php

use App\Models\OAuthUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

it('signs up and links a new oauth identity, then reuses it on the next login', function () {
    $socialiteUser = new Laravel\Socialite\Two\User;
    $socialiteUser->map([
        'id' => 'github-42',
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'avatar' => null,
    ]);
    $socialiteUser->token = 'oauth-token-1';
    $socialiteUser->refresh_token = null;

    // Same provider account id, but a rotated access token.
    $returningUser = clone $socialiteUser;
    $returningUser->token = 'oauth-token-2';

    Socialite::shouldReceive('driver')->with('github')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser, $returningUser);

    $this->get('/oauth/github/callback');

    $user = User::where('email', 'grace@example.com')->first();
    expect($user)->not->toBeNull();

    $link = OAuthUser::where('user_id', $user->id)->where('provider_type', 'github')->sole();
    expect($link->provider_user_id)->toBe('github-42');
    $this->assertAuthenticatedAs($user);

    // Tokens are encrypted at rest: the raw column is not the plain token,
    // while the model cast decrypts it back.
    $raw = DB::table('oauth_user')->where('id', $link->id)->first();
    expect($raw->provider_token)->not->toBe('oauth-token-1')
        ->and($link->provider_token)->toBe('oauth-token-1');

    auth()->logout();

    // Second login: the token rotated, so matching must go through
    // provider_user_id. No duplicate user or link is created.
    $this->get('/oauth/github/callback');

    expect(User::where('email', 'grace@example.com')->count())->toBe(1)
        ->and(OAuthUser::where('user_id', $user->id)->count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('stamps the provider user id on legacy rows linked only by token', function () {
    $user = makeUser(['email' => 'henry@example.com']);
    $legacy = OAuthUser::create([
        'provider_type' => 'github',
        'provider_token' => 'legacy-token',
        'user_id' => $user->id,
    ]);
    expect($legacy->provider_user_id)->toBeNull();

    $socialiteUser = new Laravel\Socialite\Two\User;
    $socialiteUser->map([
        'id' => 'github-77',
        'name' => 'Henry',
        'email' => 'henry@example.com',
        'avatar' => null,
    ]);
    $socialiteUser->token = 'legacy-token';
    $socialiteUser->refresh_token = null;

    Socialite::shouldReceive('driver')->with('github')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $this->get('/oauth/github/callback');

    $this->assertAuthenticatedAs($user);
    expect($legacy->fresh()->provider_user_id)->toBe('github-77')
        ->and(OAuthUser::where('user_id', $user->id)->count())->toBe(1);
});
