<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;

/**
 * Vocabulaire fermé, géré par l'administrateur.
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
        return $user->isAdmin();
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
