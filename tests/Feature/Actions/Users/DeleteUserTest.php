<?php

use App\Actions\Users\DeleteUser;
use App\Actions\Users\RestoreUser;
use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

it('supprime un compte en douceur : plus de connexion ni de jeton, cartes conservées', function () {
    $member = User::factory()->create(['email' => 'membre@example.test']);
    $member->createToken('appli');
    $card = Card::factory()->create(['author_id' => $member->id]);
    $admin = User::factory()->admin()->create();

    app(DeleteUser::class)->handle($member, $admin);

    expect($member->refresh()->trashed())->toBeTrue()
        ->and($member->tokens()->count())->toBe(0)
        ->and(auth()->attempt(['email' => 'membre@example.test', 'password' => 'password']))->toBeFalse()
        ->and($card->refresh()->author_id)->toBe($member->id)
        ->and(ActivityLog::where('action', ActivityAction::AccountDeleted)->sole()->actor_id)->toBe($admin->id);
});

it('restaure un compte supprimé', function () {
    $member = User::factory()->create(['email' => 'membre@example.test']);
    $admin = User::factory()->admin()->create();
    app(DeleteUser::class)->handle($member, $admin);

    app(RestoreUser::class)->handle($member, $admin);

    expect($member->refresh()->trashed())->toBeFalse()
        ->and(auth()->attempt(['email' => 'membre@example.test', 'password' => 'password']))->toBeTrue();
});

it('réserve la suppression à l’admin, jamais sur son propre compte', function () {
    $admin = User::factory()->admin()->create();

    expect(fn () => app(DeleteUser::class)->handle(User::factory()->create(), User::factory()->moderator()->create()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(DeleteUser::class)->handle($admin, $admin))
        ->toThrow(AuthorizationException::class);
});
