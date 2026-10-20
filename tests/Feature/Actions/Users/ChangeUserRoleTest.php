<?php

use App\Actions\Users\ChangeUserRole;
use App\Enums\ActivityAction;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

it('nomme un modérateur et le journalise', function () {
    $member = User::factory()->create();

    app(ChangeUserRole::class)->handle($member, User::factory()->admin()->create(), Role::Moderator);

    expect($member->refresh()->role)->toBe(Role::Moderator)
        ->and(ActivityLog::sole())
        ->action->toBe(ActivityAction::RoleChanged)
        ->after->toBe(['role' => 'moderator']);
});

it('refuse qu’un modérateur nomme qui que ce soit', function () {
    expect(fn () => app(ChangeUserRole::class)->handle(User::factory()->create(), User::factory()->moderator()->create(), Role::Admin))
        ->toThrow(AuthorizationException::class);
});
