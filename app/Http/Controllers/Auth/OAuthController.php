<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirectOAuth($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleOAuthCallback($provider)
    {
        $oAuthUser = Socialite::driver($provider)->user();

        $user = User::firstWhere('email', $oAuthUser->getEmail());

        if (! $user) { // OAuth Sign Up
            $user = User::create([
                'email' => $oAuthUser->getEmail(),
                'name' => $oAuthUser->getName(),
                'password' => bcrypt(Str::random()), // encrypt in case of data leaks
                'profile_picture_path' => $oAuthUser->getAvatar(),
            ]);
        }

        $userOAuthSignIn = $user->oAuthProfiles
            ->where('provider_type', $provider)
            ->where('provider_token', $oAuthUser->token)
            ->first();

        if (! $userOAuthSignIn) {
            // first time using this provider's OAuth service

            $userOAuthSignIn = OAuthUser::create([
                'provider_type' => $provider,
                'provider_token' => $oAuthUser->token,
                'provider_refresh_token' => $oAuthUser->refresh_token,
                'user_id' => $user->id,
            ]);
        } else {
            // check refresh token if needed

        }

        Auth::login($user, true);

        return redirect()->route('home');
    }
}
