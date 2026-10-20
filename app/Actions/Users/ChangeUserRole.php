<?php

namespace App\Actions\Users;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeUserRole
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $user, User $actor, Role $role): User
    {
        Gate::forUser($actor)->authorize('changeRole', $user);

        if ($user->role === $role) {
            return $user;
        }

        DB::transaction(function () use ($user, $actor, $role) {
            $before = $user->role;
            $user->role = $role;
            $user->save();

            $this->recordActivity->handle($user, $actor, ActivityAction::RoleChanged, ['role' => $before->value], ['role' => $role->value]);
        });

        return $user;
    }
}
