<?php

use App\Actions\Verdicts\ApproveVerdict;
use App\Actions\Verdicts\RejectVerdict;
use App\Actions\Verdicts\UpdateVerdict;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;
use App\Notifications\VerdictDecided;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Notification::fake());

function proposedVerdict(): Verdict
{
    $card = Card::factory()->overdue()->promise()->create(['status' => CardStatus::VerdictProposed]);

    return Verdict::factory()->create(['card_id' => $card->id, 'value' => VerdictValue::Kept]);
}

it('valide le verdict et tranche la carte', function () {
    $verdict = proposedVerdict();
    $moderator = User::factory()->moderator()->create();

    app(ApproveVerdict::class)->handle($verdict, $moderator);

    expect($verdict->refresh())
        ->status->toBe(VerdictStatus::Approved)
        ->decided_by->toBe($moderator->id)
        ->and($verdict->card->status)->toBe(CardStatus::Closed)
        ->and($verdict->activityLogs()->sole()->action)->toBe(ActivityAction::Approved)
        ->and($verdict->card->activityLogs()->sole()->after)->toBe(['status' => 'closed', 'verdict' => 'kept']);
    Notification::assertSentTo($verdict->proposer, VerdictDecided::class);
});

it('refuse le verdict avec motif et remet la carte en attente de verdict', function () {
    $verdict = proposedVerdict();

    app(RejectVerdict::class)->handle($verdict, User::factory()->moderator()->create(), 'Source sans rapport.');

    expect($verdict->refresh())
        ->status->toBe(VerdictStatus::Rejected)
        ->rejection_reason->toBe('Source sans rapport.')
        ->and($verdict->card->status)->toBe(CardStatus::Published);
    Notification::assertSentTo($verdict->proposer, VerdictDecided::class);
});

it('refuse qu’on valide son propre verdict', function () {
    $verdict = proposedVerdict();

    expect(fn () => app(ApproveVerdict::class)->handle($verdict, $verdict->proposer->forceFill(['role' => 'moderator'])))
        ->toThrow(AuthorizationException::class);
});

it('corrige le verdict proposé et journalise la correction', function () {
    $verdict = proposedVerdict();

    app(UpdateVerdict::class)->handle($verdict, User::factory()->moderator()->create(), ['value' => VerdictValue::PartiallyKept]);

    expect($verdict->refresh()->value)->toBe(VerdictValue::PartiallyKept)
        ->and($verdict->activityLogs()->sole()->after)->toBe(['value' => 'partially_kept']);
});

it('refuse une valeur de prédiction sur une promesse', function () {
    $verdict = proposedVerdict();

    expect(fn () => app(UpdateVerdict::class)->handle($verdict, User::factory()->moderator()->create(), ['value' => VerdictValue::Correct]))
        ->toThrow(ValidationException::class, 'ne correspond pas au type de la carte');
    expect($verdict->refresh()->value)->toBe(VerdictValue::Kept);
});
