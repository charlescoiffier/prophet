<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Connexion d'un client de l'API : renvoie un jeton Sanctum.
 */
class CreateApiToken
{
    public function handle(string $email, string $password, string $deviceName): string
    {
        $user = User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user->createToken($deviceName)->plainTextToken;
    }
}
