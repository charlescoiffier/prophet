<?php

namespace App\Policies;

use App\Models\PublicFigure;
use App\Models\User;

class PublicFigurePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, PublicFigure $publicFigure): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, PublicFigure $publicFigure): bool
    {
        return $user->isModerator();
    }

    /**
     * Seulement une fiche créée par erreur, sans aucune carte.
     */
    public function delete(User $user, PublicFigure $publicFigure): bool
    {
        return $user->isAdmin() && ! $publicFigure->cards()->exists();
    }
}
