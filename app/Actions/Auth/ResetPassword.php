<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Nouveau mot de passe avec le jeton reçu par e-mail. Révoque les jetons d'API du compte.
 */
class ResetPassword
{
    public function handle(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            ['email' => mb_strtolower($email), 'token' => $token, 'password' => $password],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }
}
