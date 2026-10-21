<?php

use App\Actions\Users\UpdateAccount;
use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('demande de confirmer à nouveau une adresse changée', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'ancienne@example.test']);

    app(UpdateAccount::class)->handle($user, $user, ['username' => $user->username, 'email' => 'Nouvelle@Example.test']);

    expect($user->refresh())
        ->email->toBe('nouvelle@example.test')
        ->hasVerifiedEmail()->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('garde l’adresse confirmée quand seul le nom change, sans journaliser la modification par le titulaire', function () {
    Notification::fake();
    $user = User::factory()->create();

    app(UpdateAccount::class)->handle($user, $user, ['first_name' => ' Léa ', 'last_name' => '', 'username' => $user->username, 'email' => $user->email]);

    expect($user->refresh())
        ->first_name->toBe('Léa')
        ->last_name->toBeNull()
        ->hasVerifiedEmail()->toBeTrue()
        ->and(ActivityLog::where('subject_id', $user->id)->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('journalise une modification faite par l’administrateur', function () {
    $user = User::factory()->create(['username' => 'avant'])->fresh();
    $admin = User::factory()->admin()->create();

    app(UpdateAccount::class)->handle($user, $admin, ['username' => 'apres', 'email' => $user->email]);

    $log = ActivityLog::where('subject_id', $user->id)->sole();
    expect($log->action)->toBe(ActivityAction::Edited)
        ->and($log->before)->toBe(['username' => 'avant'])
        ->and($log->after)->toBe(['username' => 'apres']);
});

it('interdit à un membre de modifier le compte d’un autre', function () {
    $user = User::factory()->create();

    app(UpdateAccount::class)->handle($user, User::factory()->moderator()->create(), ['username' => 'pirate', 'email' => $user->email]);
})->throws(AuthorizationException::class);
