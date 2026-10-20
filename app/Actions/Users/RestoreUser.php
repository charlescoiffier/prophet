<?php

namespace App\Actions\Users;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestoreUser
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('restore', $user);

        DB::transaction(function () use ($user, $actor) {
            $user->restore();
            $this->recordActivity->handle($user, $actor, ActivityAction::AccountRestored);
        });
    }
}
