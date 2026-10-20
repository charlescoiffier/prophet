<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Confirme l'adresse d'un compte depuis le lien signé reçu par e-mail.
 * L'événement Verified déclenche le rattachement des cartes (règle 8).
 */
class VerifyEmail
{
    public function handle(User $user, string $hash): bool
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return true;
    }
}
