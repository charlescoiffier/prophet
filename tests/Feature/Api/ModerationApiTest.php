<?php

use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Models\Card;
use App\Models\Report;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
});

it('exige un jeton pour les décisions', function () {
    $this->postJson('/api/v1/cards/'.Card::factory()->create()->id.'/decision', ['decision' => 'approve'])
        ->assertUnauthorized();
});

it('refuse les décisions à un membre', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/cards/'.Card::factory()->create()->id.'/decision', ['decision' => 'approve'])
        ->assertForbidden();
});

it('publie une carte', function () {
    $card = Card::factory()->create();
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->postJson("/api/v1/cards/{$card->id}/decision", ['decision' => 'approve'])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'published');
});

it('exige le motif d’un refus', function () {
    $card = Card::factory()->create();
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->postJson("/api/v1/cards/{$card->id}/decision", ['decision' => 'reject'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason' => 'Le champ motif est obligatoire quand decision vaut reject.']);
});

it('refuse qu’un modérateur publie sa propre carte (règle 4)', function () {
    $moderator = User::factory()->moderator()->create();
    $card = Card::factory()->create(['author_id' => $moderator->id]);
    Sanctum::actingAs($moderator);

    $this->postJson("/api/v1/cards/{$card->id}/decision", ['decision' => 'approve'])->assertForbidden();
});

it('modifie la rédaction et incrémente la version', function () {
    $card = Card::factory()->published()->create();
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->patchJson("/api/v1/cards/{$card->id}", ['verdict_criterion' => 'Chiffre officiel publié.'])
        ->assertOk()
        ->assertJsonPath('data.verdict_criterion', 'Chiffre officiel publié.')
        ->assertJsonPath('data.version', 2);
});

it('laisse un membre proposer un verdict, puis bloque la seconde proposition (règle 5)', function () {
    $card = Card::factory()->overdue()->prediction()->create();
    $payload = ['value' => 'wrong', 'justification' => 'Chiffre publié : 7,4 %.', 'sources' => [['url' => 'https://example.org/insee', 'kind' => 'public_data']]];

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/cards/{$card->id}/verdicts", $payload)
        ->assertCreated()
        ->assertJsonPath('data.status.value', 'proposed');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/cards/{$card->id}/verdicts", $payload)->assertForbidden();
});

it('exige une source pour proposer un verdict (règle 3)', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/cards/'.Card::factory()->overdue()->create()->id.'/verdicts', ['value' => 'wrong', 'justification' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sources');
});

it('valide un verdict et tranche la carte', function () {
    $verdict = Verdict::factory()->create();
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->postJson("/api/v1/verdicts/{$verdict->id}/decision", ['decision' => 'approve'])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'approved');

    expect($verdict->card->refresh()->status)->toBe(CardStatus::Closed);
});

it('cache une proposition de verdict au public', function () {
    $this->getJson('/api/v1/verdicts/'.Verdict::factory()->create()->id)->assertNotFound();
    $this->getJson('/api/v1/verdicts/'.Verdict::factory()->approved()->create()->id)->assertOk();
});

it('renvoie la file de modération aux seuls modérateurs', function () {
    $moderator = User::factory()->moderator()->create();
    Card::factory()->create(['author_id' => $moderator->id]);
    Verdict::factory()->create();
    Report::factory()->create();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/moderation/queue')->assertForbidden();

    Sanctum::actingAs($moderator);
    $this->getJson('/api/v1/moderation/queue')
        ->assertOk()
        ->assertJsonCount(1, 'cards')
        ->assertJsonPath('cards.0.is_own', true)
        ->assertJsonCount(1, 'verdicts')
        ->assertJsonCount(1, 'reports');

    expect(Verdict::sole()->status)->toBe(VerdictStatus::Proposed);
});
