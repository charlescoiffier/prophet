<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->is($model);
    }

    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Prénom, nom, pseudo et adresse : le titulaire du compte, ou l'administrateur.
     */
    public function update(User $user, User $model): bool
    {
        return ! $model->trashed() && ($user->isAdmin() || $user->is($model));
    }

    /**
     * L'administrateur ne change pas son propre rôle (il ne pourrait plus revenir).
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }

    /**
     * Suppression douce, par l'administrateur, jamais de son propre compte.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model) && ! $model->trashed();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->trashed();
    }

    /**
     * Pas de suppression définitive depuis le panneau : l'historique public doit rester cohérent.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
