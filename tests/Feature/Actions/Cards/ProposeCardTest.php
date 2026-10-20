<?php

use App\Actions\Cards\ProposeCard;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Jobs\ArchiveSource;
use App\Models\PublicFigure;
use App\Models\User;
use App\Notifications\ConfirmCardEmail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
});

function proposal(array $overrides = []): array
{
    return [
        'public_figure_id' => PublicFigure::factory()->create()->id,
        'type' => 'prediction',
        'quote' => '« Le chômage baissera. »',
        'stated_on' => '2026-10-01',
        'sources' => [['url' => 'https://example.org/emission', 'kind' => 'audio', 'timecode' => '08:10']],
        ...$overrides,
    ];
}

it('garde la carte d’un visiteur en attente et lui envoie le lien de confirmation (règle 1)', function () {
    $card = app(ProposeCard::class)->handle(null, proposal(['email' => 'visiteur@example.test']));

    expect($card->refresh())
        ->status->toBe(CardStatus::AwaitingEmail)
        ->author_email->toBe('visiteur@example.test')
        ->author_id->toBeNull()
        ->and($card->activityLogs()->sole()->action)->toBe(ActivityAction::Proposed);
    Notification::assertSentOnDemand(ConfirmCardEmail::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'visiteur@example.test');
});

it('envoie directement en modération la carte d’un membre', function () {
    $member = User::factory()->create();

    $card = app(ProposeCard::class)->handle($member, proposal());

    expect($card->refresh())->status->toBe(CardStatus::InModeration)->author_id->toBe($member->id);
    Notification::assertNothingSent();
});

it('archive chaque source de la proposition (règle 7)', function () {
    $card = app(ProposeCard::class)->handle(User::factory()->create(), proposal());

    Queue::assertPushed(ArchiveSource::class, fn (ArchiveSource $job) => $job->source->is($card->sources()->sole()));
});

it('exige l’adresse e-mail d’un visiteur', function () {
    expect(fn () => app(ProposeCard::class)->handle(null, proposal()))
        ->toThrow(ValidationException::class, 'Indiquez votre adresse e-mail');
});

it('exige au moins une source', function () {
    expect(fn () => app(ProposeCard::class)->handle(User::factory()->create(), proposal(['sources' => []])))
        ->toThrow(ValidationException::class, 'Une carte doit avoir au moins une source.');
});

it('refuse un membre dont l’adresse n’est pas confirmée', function () {
    expect(fn () => app(ProposeCard::class)->handle(User::factory()->unverified()->create(), proposal()))
        ->toThrow(AuthorizationException::class);
});
