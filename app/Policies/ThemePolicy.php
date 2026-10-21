<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;

/**
 * Vocabulaire géré par l'administrateur ; un modérateur peut ajouter un thème en saisissant une carte.
 */
class ThemePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Theme $theme): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, Theme $theme): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $user->isAdmin();
    }
}
