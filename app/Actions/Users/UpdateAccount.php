<?php

namespace App\Actions\Users;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Prénom, nom, pseudo et adresse d'un compte, par son titulaire ou par l'administrateur.
 * Une nouvelle adresse doit être confirmée à nouveau ; une modification par l'administrateur est journalisée.
 */
class UpdateAccount
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, username: string, email: string}  $data
     */
    public function handle(User $user, User $actor, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        $user->fill([
            'first_name' => filled($data['first_name'] ?? null) ? trim($data['first_name']) : null,
            'last_name' => filled($data['last_name'] ?? null) ? trim($data['last_name']) : null,
            'username' => $data['username'],
            'email' => mb_strtolower(trim($data['email'])),
        ]);

        if (! $user->isDirty()) {
            return $user;
        }

        $emailChanged = $user->isDirty('email');

        DB::transaction(function () use ($user, $actor, $emailChanged) {
            $changes = $user->getDirty();
            $before = array_intersect_key($user->getOriginal(), $changes);

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if (! $actor->is($user)) {
                $this->recordActivity->handle($user, $actor, ActivityAction::Edited, $before, $changes);
            }
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }
}
