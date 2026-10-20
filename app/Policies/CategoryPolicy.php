<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Un modérateur peut ajouter une catégorie en saisissant une fiche.
     */
    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin() && ! $category->publicFigures()->exists();
    }
}
