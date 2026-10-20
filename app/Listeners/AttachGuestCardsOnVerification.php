<?php

namespace App\Listeners;

use App\Actions\Users\AttachGuestCards;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Règle 8 : à la confirmation de l'adresse d'un nouveau compte, on lui rattache ses cartes.
 */
class AttachGuestCardsOnVerification
{
    public function __construct(private AttachGuestCards $attachGuestCards) {}

    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->attachGuestCards->handle($event->user);
        }
    }
}
