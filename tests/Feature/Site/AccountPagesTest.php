<?php

use App\Livewire\Account\AccountSettings;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPasswordForm;
use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

it('connecte un membre, quelle que soit la casse de l’adresse', function () {
    $user = User::factory()->create(['email' => 'membre@example.test']);

    Livewire::test(Login::class)
        ->set('email', 'Membre@Example.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('refuse un mauvais mot de passe et limite les tentatives', function () {
    User::factory()->create(['email' => 'membre@example.test']);

    foreach (range(1, 5) as $i) {
        Livewire::test(Login::class)->set('email', 'membre@example.test')->set('password', 'faux')->call('login')
            ->assertHasErrors(['email']);
    }

    Livewire::test(Login::class)->set('email', 'membre@example.test')->set('password', 'password')->call('login')
        ->assertHasErrors(['email']);
    $this->assertGuest();
});

it('crée un compte, connecte et envoie le lien de confirmation', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('username', 'nouveau')
        ->set('email', 'Nouveau@example.test')
        ->set('password', 'un mot de passe solide')
        ->set('password_confirmation', 'un mot de passe solide')
        ->set('altcha', altchaSolution())
        ->call('register')
        ->assertRedirect(route('account.cards'));

    $user = User::sole();
    expect($user->email)->toBe('nouveau@example.test');
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('envoie le lien de réinitialisation vers la page du site', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'membre@example.test']);

    Livewire::test(ForgotPassword::class)->set('email', 'membre@example.test')->call('send')->assertSee('Si un compte existe');

    Notification::assertSentTo($user, ResetPassword::class, fn (ResetPassword $notification) => str_contains(
        $notification->toMail($user)->actionUrl, '/mot-de-passe/nouveau?token='
    ));
});

it('enregistre le nouveau mot de passe depuis le lien reçu', function () {
    $user = User::factory()->create(['email' => 'membre@example.test']);
    $token = Password::createToken($user);

    $this->get('/mot-de-passe/nouveau?token='.$token.'&email=membre@example.test')->assertOk();

    Livewire::withQueryParams(['token' => $token, 'email' => 'membre@example.test'])
        ->test(ResetPasswordForm::class)
        ->set('password', 'un nouveau mot de passe')
        ->set('password_confirmation', 'un nouveau mot de passe')
        ->call('save')
        ->assertRedirect(route('login'));

    expect(auth()->attempt(['email' => 'membre@example.test', 'password' => 'un nouveau mot de passe']))->toBeTrue();
});

it('montre ses propositions au membre connecté, et à lui seul', function () {
    $member = User::factory()->create();
    Card::factory()->create(['author_id' => $member->id, 'quote' => '« Ma carte. »']);
    Card::factory()->create(['quote' => '« Celle d’un autre. »']);

    $this->get('/mes-propositions')->assertRedirect('/connexion');
    $this->actingAs($member)->get('/mes-propositions')
        ->assertOk()
        ->assertSee('« Ma carte. »')
        ->assertSee('En modération')
        ->assertDontSee('« Celle d’un autre. »');
});

it('déconnecte', function () {
    $this->actingAs(User::factory()->create())->post('/deconnexion')->assertRedirect('/');

    $this->assertGuest();
});

it('affiche les pages de texte', function (string $url, string $text) {
    $this->get($url)->assertOk()->assertSee($text);
})->with([
    ['/charte', 'Charte du contributeur'],
    ['/donnees-personnelles', 'Données personnelles'],
]);

it('montre et enregistre les informations de Mon compte', function () {
    $user = User::factory()->create(['username' => 'lecteur']);

    $this->actingAs($user)->get(route('account.settings'))->assertOk()->assertSee('Mon compte')->assertSee('Membre');

    Livewire::actingAs($user)
        ->test(AccountSettings::class)
        ->set('first_name', 'Léa')
        ->set('username', 'lectrice')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->refresh())->first_name->toBe('Léa')->username->toBe('lectrice');
});

it('refuse dans Mon compte un pseudo déjà pris', function () {
    User::factory()->create(['username' => 'pris']);

    Livewire::actingAs(User::factory()->create())
        ->test(AccountSettings::class)
        ->set('username', 'pris')
        ->call('saveProfile')
        ->assertHasErrors(['username' => 'unique']);
});

it('change le mot de passe seulement avec l’ancien', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettings::class)
        ->set('current_password', 'faux')
        ->set('password', 'un nouveau mot de passe')
        ->set('password_confirmation', 'un nouveau mot de passe')
        ->call('savePassword')
        ->assertHasErrors(['current_password'])
        ->set('current_password', 'password')
        ->call('savePassword')
        ->assertHasNoErrors();

    expect(Hash::check('un nouveau mot de passe', $user->refresh()->password))->toBeTrue();
});

it('renvoie vers la connexion pour Mon compte sans être connecté', function () {
    $this->get(route('account.settings'))->assertRedirect(route('login'));
});
