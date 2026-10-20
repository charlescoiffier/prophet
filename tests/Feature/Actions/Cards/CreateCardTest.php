<?php

use App\Actions\Cards\CreateCard;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function cardInput(array $overrides = []): array
{
    return [
        'public_figure_id' => PublicFigure::factory()->create()->id,
        'type' => 'promise',
        'quote' => '« Nous ferons X. »',
        'stated_on' => '2026-09-01',
        'deadline' => '2027-12-31',
        'themes' => [Theme::factory()->create()->id],
        'sources' => [['url' => 'https://example.org/interview', 'kind' => 'video', 'timecode' => '12:34']],
        ...$overrides,
    ];
}

it('enregistre la carte du modérateur en modération, avec thèmes, sources et journal', function () {
    $moderator = User::factory()->moderator()->create();

    $card = app(CreateCard::class)->handle($moderator, cardInput());

    expect($card->refresh())
        ->status->toBe(CardStatus::InModeration)
        ->author_id->toBe($moderator->id)
        ->and($card->themes)->toHaveCount(1)
        ->and($card->sources()->sole()->timecode)->toBe('12:34')
        ->and($card->activityLogs()->sole()->action)->toBe(ActivityAction::Proposed);
});

it('exige au moins une source', function () {
    expect(fn () => app(CreateCard::class)->handle(User::factory()->moderator()->create(), cardInput(['sources' => []])))
        ->toThrow(ValidationException::class, 'Une carte doit avoir au moins une source.');
});

it('refuse la saisie directe à un membre', function () {
    expect(fn () => app(CreateCard::class)->handle(User::factory()->create(), cardInput()))
        ->toThrow(AuthorizationException::class);
});
