<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Rules ported verbatim from the old RegisterController.
        Validator::make($input, [
            'name' => 'required|string|min:6|max:255',
            'email' => 'required|string|email|unique:user_profile',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters(),
            ],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
