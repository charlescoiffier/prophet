<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * Nouveau mot de passe choisi par le titulaire du compte (l'ancien est vérifié par la validation).
 */
class UpdatePassword
{
    public function handle(User $user, string $password): User
    {
        $user->password = $password;
        $user->save();

        return $user;
    }
}
