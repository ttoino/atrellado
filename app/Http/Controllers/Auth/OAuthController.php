<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ProviderType;
use App\Http\Controllers\Controller;
use App\Models\OAuthUser;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class OAuthController extends Controller
{
    public function redirectOAuth(string $provider)
    {
        abort_unless(ProviderType::from($provider)->isConfigured(), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function handleOAuthCallback(string $provider)
    {
        $providerType = ProviderType::from($provider);
        abort_unless($providerType->isConfigured(), 404);

        try {
            /** @var \Laravel\Socialite\Two\User $oAuthUser */
            $oAuthUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException|GuzzleException) {
            return redirect()->route('login')->with('error', "Sign-in with {$providerType->label()} failed. Please try again.");
        }

        if (! $oAuthUser->getEmail()) {
            return redirect()->route('register')->with('error', "Your {$providerType->label()} account did not share an email address. Register manually instead.");
        }

        // Primary lookup: the stable provider account id, immune to email
        // changes and token rotation.
        $userOAuthSignIn = OAuthUser::where('provider_type', $provider)
            ->where('provider_user_id', $oAuthUser->getId())
            ->first();

        $user = $userOAuthSignIn !== null
            ? $userOAuthSignIn->user
            : User::firstWhere('email', $oAuthUser->getEmail());

        if (! $user) { // OAuth Sign Up
            $user = User::create([
                'email' => $oAuthUser->getEmail(),
                'name' => $oAuthUser->getName(),
                'password' => Str::random(), // encrypted by the hashed cast
                'profile_picture_path' => $oAuthUser->getAvatar(),
            ]);
        }

        if (! $userOAuthSignIn) {
            $userOAuthSignIn = $user->oAuthProfiles
                ->where('provider_type', $provider)
                ->where('provider_token', $oAuthUser->token)
                ->first();

            if ($userOAuthSignIn) {
                // Legacy row linked by token before provider ids were stored:
                // stamp it so the next login matches by provider_user_id.
                $userOAuthSignIn->forceFill(['provider_user_id' => $oAuthUser->getId()])->save();
            } else {
                // first time using this provider's OAuth service

                OAuthUser::create([
                    'provider_type' => $provider,
                    'provider_user_id' => $oAuthUser->getId(),
                    'provider_token' => $oAuthUser->token,
                    // Socialite exposes refresh_token only through its magic __get.
                    // @phpstan-ignore property.notFound
                    'provider_refresh_token' => $oAuthUser->refresh_token,
                    'user_id' => $user->id,
                ]);
            }
        }

        Auth::login($user, true);

        return redirect()->route('home');
    }
}
