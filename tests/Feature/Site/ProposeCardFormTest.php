<?php

use App\Actions\Cards\PublishCard;
use App\Enums\CardStatus;
use App\Livewire\Cards\ProposeCardForm;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\User;
use App\Notifications\ConfirmCardEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
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

it('affiche la page de proposition, avec le menu du site', function () {
    $this->get('/proposer')
        ->assertOk()
        ->assertSee('Gardons-en la trace.', escape: false)
        ->assertSee('Citation exacte *')
        ->assertSee('Votre adresse e-mail *')
        ->assertSee('Toutes les cartes'); // menu de gauche : c'est une page du site
});

it('envoie la proposition d’un visiteur et demande de confirmer l’adresse', function () {
    fillProposal(Livewire::test(ProposeCardForm::class))
        ->set('email', 'visiteur@example.test')
        ->set('altcha', altchaSolution())
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
        fillProposal(Livewire::test(ProposeCardForm::class))->set('email', "v{$i}@example.test")->set('altcha', altchaSolution())->call('submit')->assertHasNoErrors();
    }

    fillProposal(Livewire::test(ProposeCardForm::class))
        ->set('email', 'v11@example.test')
        ->set('altcha', altchaSolution())
        ->call('submit')
        ->assertHasErrors('quote');

    expect(Card::count())->toBe(10);
});

it('laisse demander une personnalité et des thèmes absents de la liste ; la carte attend sa personnalité pour être publiée', function () {
    $member = User::factory()->create();

    fillProposal(Livewire::actingAs($member)->test(ProposeCardForm::class))
        ->set('publicFigureId', '')
        ->set('requestingFigure', true)
        ->set('requestedFigure', 'Zoé Nouvelle, maire de Ville H')
        ->set('requestedThemes', 'Agriculture, Numérique')
        ->call('submit')
        ->assertHasNoErrors();

    $card = Card::sole();
    expect($card)
        ->public_figure_id->toBeNull()
        ->requested_figure->toBe('Zoé Nouvelle, maire de Ville H')
        ->requested_themes->toBe('Agriculture, Numérique')
        ->status->toBe(CardStatus::InModeration);

    $this->actingAs($member)->get(route('cards.show', $card))->assertOk()->assertSee('Zoé Nouvelle, maire de Ville H');
    $this->actingAs($member)->get(route('account.cards'))->assertOk()->assertSee('(à ajouter)');

    expect(fn () => app(PublishCard::class)->handle($card, User::factory()->moderator()->create()))
        ->toThrow(ValidationException::class);
});

it('exige le nom de la personnalité demandée', function () {
    fillProposal(Livewire::actingAs(User::factory()->create())->test(ProposeCardForm::class))
        ->set('publicFigureId', '')
        ->set('requestingFigure', true)
        ->call('submit')
        ->assertHasErrors(['requestedFigure' => 'required']);
});
