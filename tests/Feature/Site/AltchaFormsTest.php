<?php

use App\Livewire\Auth\Register;
use App\Livewire\Cards\ProposeCardForm;
use App\Livewire\Cards\ReportForm;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\User;
use App\Support\AltchaGuard;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
});

it('affiche le widget anti-robot aux visiteurs, pas aux membres', function () {
    $this->get('/proposer')->assertSee('<altcha-widget', escape: false);
    $this->actingAs(User::factory()->create())->get('/proposer')->assertDontSee('<altcha-widget', escape: false);
});

it('refuse la proposition d’un visiteur sans vérification anti-robot', function () {
    Livewire::test(ProposeCardForm::class)
        ->set('quote', '« X. »')
        ->set('publicFigureId', PublicFigure::factory()->create()->id)
        ->set('statedOn', '2026-10-01')
        ->set('sourceUrl', 'https://example.org/x')
        ->set('email', 'visiteur@example.test')
        ->set('charter', true)
        ->call('submit')
        ->assertHasErrors(['altcha' => 'required'])
        ->assertDispatched('altcha-reset');

    expect(Card::count())->toBe(0);
});

it('ne consomme pas la solution quand un autre champ est invalide', function () {
    $payload = altchaSolution();

    Livewire::test(ProposeCardForm::class)
        ->set('email', 'visiteur@example.test')
        ->set('altcha', $payload)
        ->call('submit')
        ->assertHasErrors(['quote'])
        ->assertHasNoErrors('altcha');

    expect(app(AltchaGuard::class)->verify($payload))->toBeTrue();
});

it('refuse une solution déjà utilisée', function () {
    $payload = altchaSolution();
    $card = Card::factory()->published()->create();

    Livewire::test(ReportForm::class, ['card' => $card])
        ->set('reason', 'Erreur.')->set('email', 'a@example.test')->set('altcha', $payload)
        ->call('submit')->assertHasNoErrors();

    Livewire::test(ReportForm::class, ['card' => $card])
        ->set('reason', 'Erreur.')->set('email', 'a@example.test')->set('altcha', $payload)
        ->call('submit')->assertHasErrors(['altcha']);

    expect($card->reports()->count())->toBe(1);
});

it('dispense les membres connectés de la vérification', function () {
    $card = Card::factory()->published()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(ReportForm::class, ['card' => $card])
        ->set('reason', 'Erreur.')
        ->call('submit')
        ->assertHasNoErrors();

    expect($card->reports()->count())->toBe(1);
});

it('exige la vérification à l’inscription', function () {
    Livewire::test(Register::class)
        ->set('username', 'robot')
        ->set('email', 'robot@example.test')
        ->set('password', 'un mot de passe solide')
        ->set('password_confirmation', 'un mot de passe solide')
        ->call('register')
        ->assertHasErrors(['altcha' => 'required']);

    expect(User::count())->toBe(0);
});
