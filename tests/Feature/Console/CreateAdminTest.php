<?php

use App\Enums\Role;
use App\Models\User;

it('crée le compte administrateur avec le mot de passe saisi à l’invite', function () {
    $this->artisan('users:create-admin', ['email' => 'Charles@Example.test', 'username' => 'charles'])
        ->expectsQuestion('Mot de passe', 'un mot de passe solide')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->email)->toBe('charles@example.test')
        ->and($user->role)->toBe(Role::Admin)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(auth()->attempt(['email' => 'charles@example.test', 'password' => 'un mot de passe solide']))->toBeTrue();
});

it('promeut un compte existant sans toucher à son mot de passe', function () {
    $member = User::factory()->create(['email' => 'charles@example.test']);

    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'ignoré'])->assertSuccessful();

    expect($member->refresh()->role)->toBe(Role::Admin);
});

it('refuse un mot de passe trop court', function () {
    $this->artisan('users:create-admin', ['email' => 'charles@example.test', 'username' => 'charles'])
        ->expectsQuestion('Mot de passe', 'court')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
