<?php

use App\Models\OAuthUser;
use App\Models\User;
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

    Socialite::shouldReceive('driver')->with('github')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $this->get('/oauth/github/callback');

    $user = User::where('email', 'grace@example.com')->first();
    expect($user)->not->toBeNull();
    expect(OAuthUser::where('user_id', $user->id)->where('provider_type', 'github')->count())->toBe(1);
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    // Second login with the same token creates no duplicate user or link.
    $this->get('/oauth/github/callback');

    expect(User::where('email', 'grace@example.com')->count())->toBe(1)
        ->and(OAuthUser::where('user_id', $user->id)->count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});
