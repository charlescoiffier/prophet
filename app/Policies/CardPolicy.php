<?php

namespace App\Policies;

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;

class CardPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Card $card): bool
    {
        return $card->status->isPublic()
            || $user?->isModerator()
            || ($user !== null && $card->isAuthoredBy($user));
    }

    /**
     * Proposition publique : un visiteur (qui confirmera son e-mail, règle 1)
     * ou un membre dont l'adresse est confirmée.
     */
    public function propose(?User $user): bool
    {
        return $user === null || $user->hasVerifiedEmail();
    }

    /**
     * Saisie directe depuis la modération.
     */
    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    /**
     * Modifier la rédaction (règle 2), jamais sur sa propre carte (règle 4), sauf pour l'admin.
     */
    public function update(User $user, Card $card): bool
    {
        return $user->isModerator()
            && (! $card->isAuthoredBy($user) || $user->isAdmin())
            && ! in_array($card->status, [CardStatus::AwaitingEmail, CardStatus::Rejected], true);
    }

    /**
     * Exception à la règle 4 décidée par Charles : l'admin publie ses propres cartes.
     * PublishCard le signale alors dans l'historique public.
     */
    public function publish(User $user, Card $card): bool
    {
        return $this->decide($user, $card)
            || ($user->isAdmin() && $card->status === CardStatus::InModeration);
    }

    public function reject(User $user, Card $card): bool
    {
        return $this->decide($user, $card);
    }

    /**
     * Les cartes ne sont jamais supprimées : leur historique est public.
     */
    public function delete(User $user, Card $card): bool
    {
        return false;
    }

    private function decide(User $user, Card $card): bool
    {
        return $user->isModerator()
            && $card->status === CardStatus::InModeration
            && ! $card->isAuthoredBy($user);
    }
}
