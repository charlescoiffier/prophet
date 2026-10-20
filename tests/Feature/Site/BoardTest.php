<?php

use App\Enums\CardStatus;
use App\Livewire\Board;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\Verdict;
use App\View\Components\Sidebar;
use Livewire\Livewire;

it('affiche l’accueil groupé par sections, sans les cartes non publiques', function () {
    Card::factory()->overdue()->create(['quote' => '« Échue. »']);
    Card::factory()->published()->create(['quote' => '« À venir. »', 'deadline' => now()->addYear()]);
    Card::factory()->create(['quote' => '« En modération. »']);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Échéance dépassée, verdict attendu', '« Échue. »', 'Prochaines échéances', '« À venir. »'])
        ->assertDontSee('« En modération. »');
});

it('affiche la vue des cartes tranchées avec leur verdict', function () {
    $card = Card::factory()->overdue()->prediction()->create(['status' => CardStatus::Closed, 'quote' => '« Tranchée. »']);
    Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => 'correct']);
    Card::factory()->published()->create(['quote' => '« Ouverte. »']);

    $this->get('/tranchees')
        ->assertOk()
        ->assertSee('« Tranchée. »')
        ->assertSee('Juste')
        ->assertDontSee('« Ouverte. »');
});

it('affiche la page d’une personnalité avec son bilan en décomptes', function () {
    $figure = PublicFigure::factory()->create(['name' => 'Jeanne Exemple', 'slug' => 'jeanne-exemple']);
    $card = Card::factory()->for($figure)->overdue()->promise()->create(['status' => CardStatus::Closed]);
    Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => 'kept']);

    $this->get('/personnalites/jeanne-exemple')
        ->assertOk()
        ->assertSee('<title>Jeanne Exemple — Parole tenue</title>', escape: false)
        ->assertSee('Promesses tranchées :')
        ->assertSee('1 tenue');
});

it('filtre la page d’un thème', function () {
    $theme = Theme::factory()->create(['name' => 'Santé', 'slug' => 'sante']);
    $card = Card::factory()->published()->create(['quote' => '« Avec thème. »']);
    $card->themes()->attach($theme);
    Card::factory()->published()->create(['quote' => '« Sans thème. »']);

    $this->get('/themes/sante')->assertOk()->assertSee('« Avec thème. »')->assertDontSee('« Sans thème. »');
});

it('cherche en français et filtre par type', function () {
    Card::factory()->published()->promise()->create(['quote' => '« Nous construirons des logements. »']);
    Card::factory()->published()->prediction()->create(['quote' => '« Les logements manqueront. »']);
    Card::factory()->published()->promise()->create(['quote' => '« Rien à voir. »']);

    Livewire::test(Board::class, ['view' => 'search'])
        ->assertSee('Tapez un mot')
        ->set('q', 'logement')
        ->assertSee('« Nous construirons des logements. »')
        ->assertSee('« Les logements manqueront. »')
        ->assertDontSee('« Rien à voir. »')
        ->set('type', 'promise')
        ->assertDontSee('« Les logements manqueront. »');
});

it('répond 404 pour une personnalité inconnue', function () {
    $this->get('/personnalites/inconnue')->assertNotFound();
});

it('montre la photo d’une personnalité, ou son monogramme à défaut', function () {
    $withPhoto = PublicFigure::factory()->create(['name' => 'Jeanne Exemple', 'photo_url' => 'https://example.org/jeanne.jpg']);
    $withoutPhoto = PublicFigure::factory()->create(['name' => 'Paul Modèle', 'photo_url' => null]);
    Card::factory()->for($withPhoto)->published()->create();
    Card::factory()->for($withoutPhoto)->published()->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('src="https://example.org/jeanne.jpg"', escape: false)
        ->assertSee('>PM</span>', escape: false);
});

it('liste les cartes dont le verdict est en relecture', function () {
    Card::factory()->overdue()->create(['status' => CardStatus::VerdictProposed, 'quote' => '« En relecture. »']);
    Card::factory()->published()->create(['quote' => '« Ouverte. »']);

    $this->get('/en-relecture')->assertOk()->assertSee('« En relecture. »')->assertDontSee('« Ouverte. »');
});

it('regroupe les verdicts par tonalité, promesses et prédictions confondues', function () {
    foreach ([['promise', 'kept', '« Tenue. »'], ['prediction', 'correct', '« Juste. »'], ['prediction', 'wrong', '« Fausse. »']] as [$type, $value, $quote]) {
        $card = Card::factory()->overdue()->create(['type' => $type, 'status' => CardStatus::Closed, 'quote' => $quote]);
        Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => $value]);
    }

    $this->get('/verdicts/tenues-ou-justes')
        ->assertOk()
        ->assertSee('Tenues ou justes')
        ->assertSee('« Tenue. »')
        ->assertSee('« Juste. »')
        ->assertDontSee('« Fausse. »');

    $this->get('/verdicts/abandonnees-ou-fausses')->assertOk()->assertSee('« Fausse. »')->assertDontSee('« Juste. »');
});

it('répond 404 pour une tonalité de verdict inconnue', function () {
    $this->get('/verdicts/inconnue')->assertNotFound();
});

it('compte les promesses et les prédictions dans les onglets', function () {
    Card::factory()->count(2)->published()->promise()->create();
    Card::factory()->published()->prediction()->create();

    Livewire::test(Board::class)
        ->assertSeeHtml('Toutes les cartes <sup>3</sup>')
        ->assertSeeHtml('Promesses <sup>2</sup>')
        ->assertSeeHtml('Prédictions <sup>1</sup>');
});

it('affiche dans la barre latérale le nombre de verdicts de chaque tonalité', function () {
    $card = Card::factory()->overdue()->prediction()->create(['status' => CardStatus::Closed]);
    Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => 'partially_correct']);

    expect((new Sidebar)->toneCounts)->toBe([
        'tenues-ou-justes' => 0,
        'partielles' => 1,
        'abandonnees-ou-fausses' => 0,
        'caduques-ou-inverifiables' => 0,
    ]);
});
