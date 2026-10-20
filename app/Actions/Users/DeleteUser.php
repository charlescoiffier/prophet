<?php

namespace App\Actions\Users;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Suppression douce d'un compte par l'administrateur : il ne peut plus se connecter
 * (sessions et jetons d'API invalidés) ; ses cartes et l'historique restent ; il est restaurable.
 */
class DeleteUser
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $user);

        DB::transaction(function () use ($user, $actor) {
            $user->tokens()->delete();
            $user->setRememberToken(null);
            $user->save();
            $user->delete();

            DB::table('sessions')->where('user_id', $user->getKey())->delete();

            $this->recordActivity->handle($user, $actor, ActivityAction::AccountDeleted);
        });
    }
}
