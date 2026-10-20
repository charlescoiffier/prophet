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
     * Saisie directe depuis la modération. La proposition publique passera par une autre capacité.
     */
    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    /**
     * Modifier la rédaction (règle 2), jamais sur sa propre carte (règle 4).
     */
    public function update(User $user, Card $card): bool
    {
        return $user->isModerator()
            && ! $card->isAuthoredBy($user)
            && ! in_array($card->status, [CardStatus::AwaitingEmail, CardStatus::Rejected], true);
    }

    public function publish(User $user, Card $card): bool
    {
        return $this->decide($user, $card);
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
