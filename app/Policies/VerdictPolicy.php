<?php

namespace App\Policies;

use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;

class VerdictPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Verdict $verdict): bool
    {
        return $verdict->status === VerdictStatus::Approved
            || $user?->isModerator()
            || $verdict->proposed_by === $user?->getKey();
    }

    /**
     * Les propositions passent par propose(), avec la carte visée.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Règle 3 : seul un membre inscrit (adresse confirmée) propose un verdict.
     * Règle 5 : la carte ne doit pas déjà avoir une proposition en attente.
     */
    public function propose(User $user, Card $card): bool
    {
        return $user->hasVerifiedEmail()
            && $card->status === CardStatus::Published;
    }

    public function update(User $user, Verdict $verdict): bool
    {
        return $this->decide($user, $verdict);
    }

    public function approve(User $user, Verdict $verdict): bool
    {
        return $this->decide($user, $verdict);
    }

    public function reject(User $user, Verdict $verdict): bool
    {
        return $this->decide($user, $verdict);
    }

    public function delete(User $user, Verdict $verdict): bool
    {
        return false;
    }

    /**
     * Règle 4 : personne ne décide sur sa propre proposition.
     */
    private function decide(User $user, Verdict $verdict): bool
    {
        return $user->isModerator()
            && $verdict->status === VerdictStatus::Proposed
            && $verdict->proposed_by !== $user->getKey();
    }
}
