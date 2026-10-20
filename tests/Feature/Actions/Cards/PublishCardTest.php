<?php

use App\Actions\Cards\PublishCard;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\CardPublished;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('publie la carte, journalise et prévient l’auteur membre', function () {
    $moderator = User::factory()->moderator()->create();
    $card = Card::factory()->create();

    app(PublishCard::class)->handle($card, $moderator);

    expect($card->refresh()->status)->toBe(CardStatus::Published)
        ->and($card->published_at)->not->toBeNull()
        ->and($card->activityLogs()->sole())
        ->action->toBe(ActivityAction::Approved)
        ->actor_id->toBe($moderator->id)
        ->before->toBe(['status' => 'in_moderation'])
        ->after->toBe(['status' => 'published']);
    Notification::assertSentTo($card->author, CardPublished::class);
});

it('prévient par e-mail l’auteur sans compte et l’invite à s’inscrire', function () {
    $card = Card::factory()->anonymous('visiteur@example.test')->create(['status' => CardStatus::InModeration]);

    app(PublishCard::class)->handle($card, User::factory()->moderator()->create());

    Notification::assertSentOnDemand(CardPublished::class, function (CardPublished $notification, array $channels, AnonymousNotifiable $notifiable) {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === 'visiteur@example.test' && $mail->actionText === 'Créer un compte';
    });
});

it('refuse que le modérateur publie sa propre carte', function () {
    $moderator = User::factory()->moderator()->create();
    $card = Card::factory()->create(['author_id' => $moderator->id]);

    expect(fn () => app(PublishCard::class)->handle($card, $moderator))->toThrow(AuthorizationException::class);
    expect($card->refresh()->status)->toBe(CardStatus::InModeration);
    Notification::assertNothingSent();
});
