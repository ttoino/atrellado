<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        // Rule ported verbatim from the old PasswordRecoveryController.
        Validator::make($input, [
            'password' => 'required|min:8|confirmed',
        ])->validate();

        // The hashed cast encrypts; remember token rotation and the
        // PasswordReset event are handled by Fortify's CompletePasswordReset.
        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
