<?php

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\User;
use App\Models\Verdict;
use Laravel\Sanctum\Sanctum;

it('affiche une personnalité avec son bilan en décomptes', function () {
    $figure = PublicFigure::factory()->create(['slug' => 'lucie-temoin']);
    foreach (['kept', 'kept', 'abandoned'] as $value) {
        $card = Card::factory()->for($figure)->promise()->overdue()->create(['status' => CardStatus::Closed]);
        Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => $value]);
    }

    $this->getJson('/api/v1/public-figures/lucie-temoin')
        ->assertOk()
        ->assertJsonPath('data.cards_count', 3)
        ->assertJsonPath('record.promise.kept', 2)
        ->assertJsonPath('record.promise.abandoned', 1)
        ->assertJsonPath('record.prediction.correct', 0);
});

it('laisse un modérateur créer une personnalité', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->postJson('/api/v1/public-figures', [
        'name' => 'Élise Démo',
        'category' => 'politique',
        'affiliations' => [['organization' => 'Parti A', 'role' => 'Députée', 'started_on' => '2022-06-01']],
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'elise-demo')
        ->assertJsonPath('data.affiliations.0.organization', 'Parti A');
});

it('refuse la création à un membre', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/public-figures', ['name' => 'X', 'category' => 'autre'])->assertForbidden();
});

it('liste les thèmes avec leur nombre de cartes publiques', function () {
    $card = Card::factory()->published()->create();
    $card->themes()->attach(Theme::factory()->create(['name' => 'Santé']));

    $this->getJson('/api/v1/themes')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Santé')
        ->assertJsonPath('data.0.cards_count', 1);
});
