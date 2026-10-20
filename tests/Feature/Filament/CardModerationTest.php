<?php

use App\Enums\CardStatus;
use App\Filament\Resources\Cards\Pages\CreateCard;
use App\Filament\Resources\Cards\Pages\EditCard;
use App\Filament\Resources\Cards\Pages\ListCards;
use App\Filament\Resources\Cards\Pages\ViewCard;
use App\Filament\Resources\Cards\RelationManagers\SourcesRelationManager;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Source;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
    $this->moderator = User::factory()->moderator()->create();
    $this->actingAs($this->moderator);
});

it('publie une carte depuis la liste', function () {
    $card = Card::factory()->create();

    Livewire::test(ListCards::class)
        ->callAction(TestAction::make('publish')->table($card))
        ->assertNotified('Carte publiée');

    expect($card->refresh()->status)->toBe(CardStatus::Published);
});

it('refuse une carte avec un motif', function () {
    $card = Card::factory()->create();

    Livewire::test(ViewCard::class, ['record' => $card->getRouteKey()])
        ->callAction('reject', ['reason' => 'Doublon.'])
        ->assertHasNoActionErrors();

    expect($card->refresh())->status->toBe(CardStatus::Rejected)->rejection_reason->toBe('Doublon.');
});

it('exige le motif du refus dans la fenêtre', function () {
    $card = Card::factory()->create();

    Livewire::test(ViewCard::class, ['record' => $card->getRouteKey()])
        ->callAction('reject', ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($card->refresh()->status)->toBe(CardStatus::InModeration);
});

it('masque Publier et Modifier sur sa propre carte', function () {
    $card = Card::factory()->create(['author_id' => $this->moderator->id]);

    Livewire::test(ListCards::class)
        ->assertActionHidden(TestAction::make('publish')->table($card))
        ->assertActionHidden(TestAction::make('reject')->table($card))
        ->assertActionHidden(TestAction::make('edit')->table($card));
});

it('saisit une carte avec sa source, qui part en modération', function () {
    $figure = PublicFigure::factory()->create();

    Livewire::test(CreateCard::class)
        ->fillForm([
            'public_figure_id' => $figure->id,
            'type' => 'prediction',
            'quote' => '« Il pleuvra. »',
            'stated_on' => '2026-10-01',
            'sources' => [['url' => 'https://example.org/meteo', 'kind' => 'article']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Card::sole())
        ->status->toBe(CardStatus::InModeration)
        ->author_id->toBe($this->moderator->id)
        ->and(Card::sole()->sources()->sole()->url)->toBe('https://example.org/meteo');
});

it('modifie la rédaction et journalise via l’action', function () {
    $card = Card::factory()->published()->create(['verdict_criterion' => null]);

    Livewire::test(EditCard::class, ['record' => $card->getRouteKey()])
        ->fillForm(['verdict_criterion' => 'Taux publié par l’organisme de référence.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($card->refresh()->version)->toBe(2)
        ->and($card->activityLogs()->sole()->after)->toBe(['verdict_criterion' => 'Taux publié par l’organisme de référence.']);
});

it('affiche l’erreur quand on retire la dernière source', function () {
    $card = Card::factory()->published()->create();
    $source = Source::factory()->for($card)->create();

    Livewire::test(SourcesRelationManager::class, ['ownerRecord' => $card, 'pageClass' => ViewCard::class])
        ->callAction(TestAction::make('remove')->table($source))
        ->assertNotified('Impossible de retirer la dernière source : ajoutez-en une autre d’abord.');

    expect($card->sources()->count())->toBe(1);
});
