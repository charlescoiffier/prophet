<?php

use App\Actions\Cards\AddSource;
use App\Actions\Cards\RemoveSource;
use App\Enums\ActivityAction;
use App\Models\Card;
use App\Models\Source;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('ajoute une source et le journalise', function () {
    $card = Card::factory()->published()->create();

    app(AddSource::class)->handle($card, User::factory()->moderator()->create(), ['url' => 'https://example.org/a', 'kind' => 'article']);

    expect($card->sources()->sole()->url)->toBe('https://example.org/a')
        ->and($card->activityLogs()->sole()->action)->toBe(ActivityAction::SourceAdded);
});

it('retire une source quand il en reste une autre', function () {
    $card = Card::factory()->published()->create();
    [$first] = Source::factory()->count(2)->for($card)->create();

    app(RemoveSource::class)->handle($first, User::factory()->moderator()->create());

    expect($card->sources()->count())->toBe(1)
        ->and($card->activityLogs()->sole()->action)->toBe(ActivityAction::SourceRemoved);
});

it('refuse de retirer la dernière source d’une carte', function () {
    $source = Source::factory()->for(Card::factory()->published())->create();

    expect(fn () => app(RemoveSource::class)->handle($source, User::factory()->moderator()->create()))
        ->toThrow(ValidationException::class, 'Impossible de retirer la dernière source');
});
