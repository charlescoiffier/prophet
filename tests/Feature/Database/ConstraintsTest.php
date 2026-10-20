<?php

use App\Enums\VerdictStatus;
use App\Models\Card;
use App\Models\Source;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Database\QueryException;

/*
 * Garde-fous posés directement dans PostgreSQL, en plus des Policies.
 */

it('interdit de valider son propre verdict (règle 4)', function () {
    $moderator = User::factory()->moderator()->create();
    $verdict = Verdict::factory()->create(['proposed_by' => $moderator->id]);

    $verdict->forceFill([
        'status' => VerdictStatus::Approved,
        'decided_by' => $moderator->id,
        'decided_at' => now(),
    ])->save();
})->throws(QueryException::class, 'verdicts_not_self_decided');

it('n’accepte qu’une proposition de verdict en attente par carte (règle 5)', function () {
    $card = Card::factory()->overdue()->create();
    Verdict::factory()->create(['card_id' => $card->id]);

    Verdict::factory()->create(['card_id' => $card->id]);
})->throws(QueryException::class, 'verdicts_one_pending_per_card');

it('accepte une nouvelle proposition une fois la précédente refusée', function () {
    $card = Card::factory()->overdue()->create();
    Verdict::factory()->create(['card_id' => $card->id, 'status' => VerdictStatus::Rejected]);

    Verdict::factory()->create(['card_id' => $card->id]);

    expect($card->verdicts()->count())->toBe(2)
        ->and($card->pendingVerdict)->not->toBeNull();
});

it('n’accepte qu’un verdict validé par carte', function () {
    $card = Card::factory()->overdue()->create();
    Verdict::factory()->approved()->create(['card_id' => $card->id]);

    Verdict::factory()->approved()->create(['card_id' => $card->id]);
})->throws(QueryException::class, 'verdicts_one_approved_per_card');

it('exige qu’une source étaye une carte ou un verdict, pas les deux', function () {
    $verdict = Verdict::factory()->create();

    Source::factory()->create(['card_id' => $verdict->card_id, 'verdict_id' => $verdict->id]);
})->throws(QueryException::class, 'sources_single_owner');

it('exige qu’une source ait un rattachement', function () {
    Source::factory()->create(['card_id' => null]);
})->throws(QueryException::class, 'sources_single_owner');

it('exige un auteur, membre ou adresse e-mail', function () {
    Card::factory()->create(['author_id' => null, 'author_email' => null]);
})->throws(QueryException::class, 'cards_author_present');
