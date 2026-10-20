<?php

use App\Actions\Cards\RejectCard;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\CardRejected;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Notification::fake());

it('refuse la carte avec son motif, journalisé et envoyé à l’auteur', function () {
    $card = Card::factory()->create();

    app(RejectCard::class)->handle($card, User::factory()->moderator()->create(), 'Citation introuvable dans la vidéo.');

    expect($card->refresh())
        ->status->toBe(CardStatus::Rejected)
        ->rejection_reason->toBe('Citation introuvable dans la vidéo.')
        ->and($card->activityLogs()->sole())
        ->action->toBe(ActivityAction::Rejected)
        ->comment->toBe('Citation introuvable dans la vidéo.');
    Notification::assertSentTo($card->author, CardRejected::class);
});

it('exige un motif', function () {
    $card = Card::factory()->create();

    expect(fn () => app(RejectCard::class)->handle($card, User::factory()->moderator()->create(), '   '))
        ->toThrow(ValidationException::class, 'Indiquez le motif du refus.');
    expect($card->refresh()->status)->toBe(CardStatus::InModeration);
});
