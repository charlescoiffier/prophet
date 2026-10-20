<?php

use App\Enums\CardStatus;
use App\Models\ActivityLog;
use App\Models\Affiliation;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\User;
use App\Models\Verdict;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Carbon;

it('reconnaît l’auteur par son compte ou par son adresse e-mail', function () {
    $user = User::factory()->create(['email' => 'Auteur@Example.test']);

    expect(Card::factory()->create(['author_id' => $user->id])->isAuthoredBy($user))->toBeTrue()
        ->and(Card::factory()->anonymous('auteur@example.test')->create()->isAuthoredBy($user))->toBeTrue()
        ->and(Card::factory()->create()->isAuthoredBy($user))->toBeFalse();
});

it('ne montre au public que les cartes publiées, en attente de verdict ou tranchées', function () {
    Card::factory()->anonymous()->create();
    Card::factory()->create();
    Card::factory()->rejected()->create();
    Card::factory()->published()->create();
    Card::factory()->overdue()->create(['status' => CardStatus::Closed]);

    expect(Card::visible()->count())->toBe(2);
});

it('liste les cartes publiées dont l’échéance est dépassée', function () {
    Card::factory()->published()->create();
    $overdue = Card::factory()->overdue()->create();

    expect(Card::awaitingVerdict()->pluck('id')->all())->toBe([$overdue->id]);
});

it('retrouve l’affiliation au moment de la déclaration', function () {
    $figure = PublicFigure::factory()->create();
    Affiliation::factory()->for($figure)->create(['organization' => 'Parti A', 'started_on' => '2015-01-01', 'ended_on' => '2020-12-31']);
    Affiliation::factory()->for($figure)->create(['organization' => 'Parti B', 'started_on' => '2021-01-01', 'ended_on' => null]);

    expect($figure->affiliationOn(Carbon::parse('2018-06-01'))->organization)->toBe('Parti A')
        ->and($figure->affiliationOn(Carbon::parse('2026-06-01'))->organization)->toBe('Parti B')
        ->and($figure->affiliationOn(Carbon::parse('2010-06-01')))->toBeNull();
});

it('choisit une valeur de verdict compatible avec le type de la carte', function () {
    $verdicts = Verdict::factory()->count(5)->create();

    foreach ($verdicts as $verdict) {
        expect($verdict->value->appliesTo($verdict->card->type))->toBeTrue();
    }
});

it('ne laisse pas remplir le statut ni l’auteur en masse', function () {
    $card = new Card(['status' => 'published', 'author_id' => 'x', 'version' => 9, 'quote' => '« … »']);

    expect($card->status)->toBeNull()
        ->and($card->author_id)->toBeNull()
        ->and($card->quote)->toBe('« … »');
});

it('ne laisse pas un utilisateur choisir son rôle', function () {
    expect((new User(['role' => 'admin']))->isAdmin())->toBeFalse();
});

it('charge le jeu de démonstration', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Card::count())->toBe(9)
        ->and(Card::visible()->count())->toBe(7)
        ->and(Card::where('status', CardStatus::Closed)->has('approvedVerdict')->count())->toBe(3);
});

it('résume les changements du journal avec les libellés français', function () {
    $log = ActivityLog::factory()->create([
        'before' => ['status' => 'in_moderation', 'quote' => 'Avant'],
        'after' => ['status' => 'published', 'quote' => 'Après', 'themes' => ['Santé', 'Emploi']],
    ]);

    expect($log->describeChanges())->toBe(implode("\n", [
        'statut : En modération → Publiée',
        'citation : Avant → Après',
        'thèmes : ∅ → Santé, Emploi',
    ]));
});
