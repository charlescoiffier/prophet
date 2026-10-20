<?php

namespace App\Policies;

use App\Enums\VerdictStatus;
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
     * Les propositions de verdict viennent des membres (étape 3), pas du panneau.
     */
    public function create(User $user): bool
    {
        return false;
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
