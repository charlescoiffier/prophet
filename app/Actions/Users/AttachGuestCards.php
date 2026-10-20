<?php

namespace App\Actions\Users;

use App\Models\Card;
use App\Models\User;

/**
 * Règle 8 : les cartes proposées sans compte avec la même adresse sont rattachées
 * au compte, une fois l'adresse confirmée (sinon n'importe qui pourrait s'approprier
 * les cartes d'un autre en s'inscrivant avec son adresse).
 *
 * L'adresse n'est plus conservée sur la carte : le compte suffit (RGPD).
 */
class AttachGuestCards
{
    public function handle(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Card::whereNull('author_id')
            ->whereNotNull('email_confirmed_at')
            ->whereRaw('lower(author_email) = ?', [mb_strtolower($user->email)])
            ->update(['author_id' => $user->getKey(), 'author_email' => null]);
    }
}
