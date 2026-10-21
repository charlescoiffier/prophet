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

it('ouvre la recherche dans la barre d’outils et filtre les cartes en direct, en français', function () {
    Card::factory()->published()->promise()->create(['quote' => '« Nous construirons des logements. »']);
    Card::factory()->published()->prediction()->create(['quote' => '« Les logements manqueront. »']);
    Card::factory()->published()->promise()->create(['quote' => '« Rien à voir. »']);

    Livewire::test(Board::class)
        ->assertSet('searching', false)
        ->set('searching', true)
        ->assertSee('« Rien à voir. »')
        ->set('q', 'logement')
        ->assertSee('« Nous construirons des logements. »')
        ->assertSee('« Les logements manqueront. »')
        ->assertDontSee('« Rien à voir. »')
        ->set('type', 'promise')
        ->assertDontSee('« Les logements manqueront. »')
        ->call('closeSearch')
        ->assertSet('q', '')
        ->assertSee('« Rien à voir. »');
});

it('ouvre l’accueil avec la recherche ouverte depuis /recherche', function () {
    Livewire::test(Board::class, ['view' => 'search'])->assertSet('searching', true)->assertSet('view', 'all');
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

it('cherche dans la citation seulement, sans accents ni majuscules, mot par mot', function () {
    $ebauche = PublicFigure::factory()->create(['name' => 'Thomas Ébauche']);
    Card::factory()->published()->for($ebauche)->create(['quote' => '« Les prix de l’électricité baisseront. »']);
    Card::factory()->published()->for($ebauche)->create(['quote' => '« Le déficit repassera sous 3 %. »']);
    Card::factory()->published()->create(['quote' => '« Les prix du métro augmenteront. »']);

    Livewire::test(Board::class)
        ->set('q', 'EBAUCHE')
        ->assertDontSee('« Les prix de l’électricité baisseront. »')
        ->set('q', 'electricite')
        ->assertSee('« Les prix de l’électricité baisseront. »')
        ->assertDontSee('« Le déficit repassera sous 3 %. »')
        ->set('q', 'prix BAISSERONT')
        ->assertSee('« Les prix de l’électricité baisseront. »')
        ->assertDontSee('« Les prix du métro augmenteront. »');
});

it('filtre les cartes par état, verdict, personnalité et thème, plusieurs choix à la fois', function () {
    $ada = PublicFigure::factory()->create(['name' => 'Ada Filtre']);
    $logement = Theme::factory()->create(['name' => 'Logement']);
    Card::factory()->published()->for($ada)->hasAttached($logement)->create(['quote' => '« À venir. »', 'deadline' => now()->addYear()]);
    Card::factory()->overdue()->create(['quote' => '« Échue. »']);
    $kept = Card::factory()->overdue()->for($ada)->create(['status' => CardStatus::Closed, 'quote' => '« Tenue. »']);
    Verdict::factory()->approved()->create(['card_id' => $kept->id, 'value' => 'kept']);
    $abandoned = Card::factory()->overdue()->create(['status' => CardStatus::Closed, 'quote' => '« Abandonnée. »']);
    Verdict::factory()->approved()->create(['card_id' => $abandoned->id, 'value' => 'abandoned']);

    Livewire::test(Board::class)
        ->set('states', ['upcoming', 'awaiting'])
        ->assertSee(['« À venir. »', '« Échue. »'])
        ->assertDontSee('« Tenue. »')
        ->set('states', [])
        ->set('verdicts', ['tenues-ou-justes'])
        ->assertSee('« Tenue. »')
        ->assertDontSee(['« Abandonnée. »', '« À venir. »'])
        ->set('verdicts', [])
        ->set('figures', [$ada->slug])
        ->assertSee(['« À venir. »', '« Tenue. »'])
        ->assertDontSee(['« Échue. »', '« Abandonnée. »'])
        ->set('themes', [$logement->slug])
        ->assertSee('« À venir. »')
        ->assertDontSee('« Tenue. »')
        ->call('clearFilters')
        ->assertSee(['« Échue. »', '« Abandonnée. »']);
});

it('liste dans la barre latérale les personnalités les plus fournies, puis les plus récemment modifiées, avec un lien vers toutes', function () {
    $figures = PublicFigure::factory()->count(Sidebar::LIMIT + 1)->create();
    foreach ($figures as $index => $figure) {
        Card::factory()->published()->for($figure)->create(['updated_at' => now()->subDays($index + 1)]);
    }
    Card::factory()->published()->for($figures->last())->create(['updated_at' => now()->subYear()]);
    Card::factory()->published()->for($figures[1])->create(['updated_at' => now()]);

    $sidebar = new Sidebar;

    expect($sidebar->figures->pluck('id')->take(3)->all())->toBe([$figures[1]->id, $figures->last()->id, $figures[0]->id])
        ->and($sidebar->figures)->toHaveCount(Sidebar::LIMIT)
        ->and($sidebar->figuresTotal)->toBe(Sidebar::LIMIT + 1);

    $this->get('/')->assertSee('Toutes les personnalités');
});

it('liste toutes les personnalités et tous les thèmes qui ont une carte publique', function () {
    $theme = Theme::factory()->create(['name' => 'Logement']);
    Card::factory()->published()->for(PublicFigure::factory()->state(['name' => 'Ada Visible']))->hasAttached($theme)->create();
    Card::factory()->for(PublicFigure::factory()->state(['name' => 'Bob Caché']))->create();

    $this->get(route('figures.index'))->assertOk()->assertSee('Ada Visible')->assertDontSee('Bob Caché');
    $this->get(route('themes.index'))->assertOk()->assertSee('# Logement');
});

it('affiche en haut la version et le commit de construction, avec un lien vers ce commit', function () {
    config(['prophet.commit' => 'abc1234def5678']);

    $this->get('/')
        ->assertSee('Version '.config('prophet.version').' · abc1234')
        ->assertSee(config('prophet.repository').'/commit/abc1234def5678', escape: false)
        ->assertDontSee('GitHub');
});
