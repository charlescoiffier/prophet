<?php

namespace App\Policies;

use App\Models\Source;
use App\Models\User;

class SourcePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Retirer une source revient à modifier la carte ou le verdict qu'elle étaye.
     */
    public function delete(User $user, Source $source): bool
    {
        $owner = $source->card ?? $source->verdict;

        return $user->can('update', $owner);
    }
}
