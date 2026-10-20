<?php

use App\Enums\CardStatus;
use App\Livewire\Cards\ProposeCardForm;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\User;
use App\Notifications\ConfirmCardEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
});

function fillProposal($component)
{
    return $component
        ->set('quote', '« Nous ferons X. »')
        ->set('publicFigureId', PublicFigure::factory()->create()->id)
        ->set('statedOn', '2026-10-01')
        ->set('type', 'promise')
        ->set('sourceUrl', 'https://example.org/entretien')
        ->set('sourceKind', 'video')
        ->set('timecode', '12:34')
        ->set('charter', true);
}

it('affiche l’ajout rapide', function () {
    $this->get('/proposer')->assertOk()->assertSee('Citation exacte, mot pour mot', escape: false)->assertSee('Votre e-mail');
});

it('envoie la proposition d’un visiteur et demande de confirmer l’adresse', function () {
    fillProposal(Livewire::test(ProposeCardForm::class))
        ->set('email', 'visiteur@example.test')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Vérifiez votre boîte de réception');

    expect(Card::sole())->status->toBe(CardStatus::AwaitingEmail)
        ->and(Card::sole()->sources()->sole()->timecode)->toBe('12:34');
    Notification::assertSentOnDemand(ConfirmCardEmail::class);
});

it('envoie en relecture la proposition d’un membre', function () {
    fillProposal(Livewire::actingAs(User::factory()->create())->test(ProposeCardForm::class))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('votre carte part en relecture');

    expect(Card::sole()->status)->toBe(CardStatus::InModeration);
});

it('exige la charte, la source et l’adresse d’un visiteur', function () {
    Livewire::test(ProposeCardForm::class)
        ->set('quote', '« X. »')
        ->call('submit')
        ->assertHasErrors(['charter' => 'accepted', 'sourceUrl' => 'required', 'email' => 'required', 'publicFigureId' => 'required']);

    expect(Card::count())->toBe(0);
});

it('limite les propositions par adresse IP', function () {
    foreach (range(1, 10) as $i) {
        fillProposal(Livewire::test(ProposeCardForm::class))->set('email', "v{$i}@example.test")->call('submit')->assertHasNoErrors();
    }

    fillProposal(Livewire::test(ProposeCardForm::class))
        ->set('email', 'v11@example.test')
        ->call('submit')
        ->assertHasErrors('quote');

    expect(Card::count())->toBe(10);
});
