<?php

use App\Enums\Role;
use App\Models\User;

it('crée le compte administrateur avec le mot de passe saisi deux fois', function () {
    $this->artisan('users:create-admin', ['email' => 'Charles@Example.test', 'username' => 'charles'])
        ->expectsQuestion('Mot de passe (10 caractères au moins)', 'un mot de passe solide')
        ->expectsQuestion('Confirmer le mot de passe', 'un mot de passe solide')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->email)->toBe('charles@example.test')
        ->and($user->role)->toBe(Role::Admin)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(auth()->attempt(['email' => 'charles@example.test', 'password' => 'un mot de passe solide']))->toBeTrue();
});

it('refuse deux saisies différentes', function () {
    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'charles'])
        ->expectsQuestion('Mot de passe (10 caractères au moins)', 'un mot de passe solide')
        ->expectsQuestion('Confirmer le mot de passe', 'un mot de passe solidE')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuse un mot de passe trop court', function () {
    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'charles'])
        ->expectsQuestion('Mot de passe (10 caractères au moins)', 'court')
        ->expectsQuestion('Confirmer le mot de passe', 'court')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('promeut un compte existant sans toucher à son mot de passe', function () {
    $member = User::factory()->create(['email' => 'charles@example.test']);

    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'ignoré'])->assertSuccessful();

    expect($member->refresh()->role)->toBe(Role::Admin)
        ->and(auth()->attempt(['email' => 'charles@example.test', 'password' => 'password']))->toBeTrue();
});

it('redéfinit le mot de passe d’un compte existant avec --password', function () {
    User::factory()->admin()->create(['email' => 'charles@example.test']);

    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'charles', '--password' => true])
        ->expectsQuestion('Mot de passe (10 caractères au moins)', 'un nouveau mot de passe')
        ->expectsQuestion('Confirmer le mot de passe', 'un nouveau mot de passe')
        ->assertSuccessful();

    expect(auth()->attempt(['email' => 'charles@example.test', 'password' => 'un nouveau mot de passe']))->toBeTrue();
});
