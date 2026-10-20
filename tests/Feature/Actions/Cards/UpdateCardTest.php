<?php

use App\Actions\Cards\UpdateCard;
use App\Enums\ActivityAction;
use App\Models\Card;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\CardEdited;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('modifie la rédaction, incrémente la version et journalise seulement ce qui change', function () {
    $card = Card::factory()->published()->create(['quote' => 'Avant', 'context' => 'Inchangé']);

    app(UpdateCard::class)->handle($card, User::factory()->moderator()->create(), [
        'quote' => 'Après',
        'context' => 'Inchangé',
    ]);

    expect($card->refresh())
        ->quote->toBe('Après')
        ->version->toBe(2)
        ->and($card->activityLogs()->sole())
        ->action->toBe(ActivityAction::Edited)
        ->before->toBe(['quote' => 'Avant'])
        ->after->toBe(['quote' => 'Après']);
    Notification::assertSentTo($card->author, CardEdited::class);
});

it('journalise le changement de thèmes', function () {
    $card = Card::factory()->published()->create();
    $theme = Theme::factory()->create(['name' => 'Santé']);

    app(UpdateCard::class)->handle($card, User::factory()->moderator()->create(), ['themes' => [$theme->id]]);

    expect($card->activityLogs()->sole()->after)->toBe(['themes' => ['Santé']]);
});

it('ne fait rien si rien ne change', function () {
    $card = Card::factory()->published()->create(['quote' => 'Même']);

    app(UpdateCard::class)->handle($card, User::factory()->moderator()->create(), ['quote' => 'Même']);

    expect($card->refresh()->version)->toBe(1)
        ->and($card->activityLogs()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('ne prévient pas l’auteur tant que la carte est en modération', function () {
    $card = Card::factory()->create(['quote' => 'Avant']);

    app(UpdateCard::class)->handle($card, User::factory()->moderator()->create(), ['quote' => 'Après']);

    Notification::assertNothingSent();
});
