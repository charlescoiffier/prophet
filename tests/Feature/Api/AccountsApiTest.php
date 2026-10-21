<?php

use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

it('crée un compte membre et envoie le lien de confirmation', function () {
    Notification::fake();

    $this->postJson('/api/v1/accounts', [
        'username' => 'nouveau',
        'email' => 'nouveau@example.test',
        'password' => 'un mot de passe solide',
        'password_confirmation' => 'un mot de passe solide',
        'altcha' => altchaSolution(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.role.value', 'member')
        ->assertJsonPath('data.email_verified', false);

    Notification::assertSentTo(User::sole(), VerifyEmail::class);
});

it('confirme l’adresse par le lien et rattache les cartes du visiteur (règle 8)', function () {
    $user = User::factory()->unverified()->create(['email' => 'visiteur@example.test']);
    $card = Card::factory()->anonymous('visiteur@example.test')->published()->create(['email_confirmed_at' => now()]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('visiteur@example.test'),
    ]);

    $this->get($url)->assertOk()->assertSee('votre adresse est confirmée');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and($card->refresh()->author_id)->toBe($user->id);
});

it('refuse un lien de confirmation dont l’empreinte ne correspond pas', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('autre@example.test')]);

    $this->get($url)->assertForbidden();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('délivre un jeton puis le révoque à la déconnexion', function () {
    User::factory()->create(['email' => 'membre@example.test']);

    $token = $this->postJson('/api/v1/sessions', ['email' => 'Membre@example.test', 'password' => 'password', 'device_name' => 'tests'])
        ->assertCreated()
        ->json('token');

    $this->withToken($token)->getJson('/api/v1/accounts/me')->assertOk()->assertJsonPath('data.email', 'membre@example.test');
    $this->withToken($token)->deleteJson('/api/v1/sessions/current')->assertNoContent();

    expect(User::sole()->tokens()->count())->toBe(0);
});

it('refuse un mauvais mot de passe', function () {
    User::factory()->create(['email' => 'membre@example.test']);

    $this->postJson('/api/v1/sessions', ['email' => 'membre@example.test', 'password' => 'faux', 'device_name' => 'tests'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
});

it('répond pareil au mot de passe oublié, que le compte existe ou non', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'membre@example.test']);

    $this->postJson('/api/v1/password/forgot', ['email' => 'membre@example.test'])->assertAccepted();
    $this->postJson('/api/v1/password/forgot', ['email' => 'inconnu@example.test'])->assertAccepted();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        return str_starts_with($notification->toMail($user)->actionUrl, url('/mot-de-passe/nouveau?token='));
    });
});

it('réinitialise le mot de passe et révoque les jetons', function () {
    $user = User::factory()->create(['email' => 'membre@example.test']);
    $user->createToken('ancien');
    $token = Password::createToken($user);

    $this->postJson('/api/v1/password/reset', [
        'token' => $token,
        'email' => 'membre@example.test',
        'password' => 'un nouveau mot de passe',
        'password_confirmation' => 'un nouveau mot de passe',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
    $this->postJson('/api/v1/sessions', ['email' => 'membre@example.test', 'password' => 'un nouveau mot de passe', 'device_name' => 'tests'])->assertCreated();
});

it('ignore un rôle envoyé à l’inscription', function () {
    Notification::fake();

    $this->postJson('/api/v1/accounts', [
        'username' => 'malin',
        'email' => 'malin@example.test',
        'password' => 'un mot de passe solide',
        'password_confirmation' => 'un mot de passe solide',
        'altcha' => altchaSolution(),
        'role' => 'admin',
    ])->assertCreated()->assertJsonPath('data.role.value', 'member');

    expect(User::sole()->isModerator())->toBeFalse();
});

it('modifie mon compte et mon mot de passe par l’API', function () {
    $user = User::factory()->create(['username' => 'lecteur']);
    $token = $user->createToken('tests')->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/v1/accounts/me', ['first_name' => 'Léa', 'username' => 'lectrice', 'email' => $user->email])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Léa')
        ->assertJsonPath('data.username', 'lectrice');

    $this->withToken($token)
        ->putJson('/api/v1/accounts/me/password', ['current_password' => 'faux', 'password' => 'un nouveau mot de passe', 'password_confirmation' => 'un nouveau mot de passe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');

    $this->withToken($token)
        ->putJson('/api/v1/accounts/me/password', ['current_password' => 'password', 'password' => 'un nouveau mot de passe', 'password_confirmation' => 'un nouveau mot de passe'])
        ->assertOk();

    expect(Hash::check('un nouveau mot de passe', $user->refresh()->password))->toBeTrue();
});
