<?php

use App\Actions\PublicFigures\SavePublicFigure;
use App\Enums\ActivityAction;
use App\Models\User;

it('crée la fiche avec un slug et ses affiliations', function () {
    $figure = app(SavePublicFigure::class)->handle(null, User::factory()->moderator()->create(), [
        'name' => 'Élise Démo',
        'category' => 'politician',
        'affiliations' => [['organization' => 'Parti A', 'role' => 'Députée', 'started_on' => '2020-01-01']],
    ]);

    expect($figure->slug)->toBe('elise-demo')
        ->and($figure->affiliations()->sole()->organization)->toBe('Parti A')
        ->and($figure->activityLogs()->sole()->action)->toBe(ActivityAction::Proposed);
});

it('met à jour, retire les affiliations disparues et journalise la différence', function () {
    $moderator = User::factory()->moderator()->create();
    $figure = app(SavePublicFigure::class)->handle(null, $moderator, [
        'name' => 'Élise Démo',
        'category' => 'politician',
        'affiliations' => [['organization' => 'Parti A'], ['organization' => 'Parti B']],
    ]);
    $keep = $figure->affiliations()->where('organization', 'Parti B')->sole();

    app(SavePublicFigure::class)->handle($figure, $moderator, [
        'name' => 'Élise Démo',
        'category' => 'politician',
        'description' => 'Députée',
        'affiliations' => [['id' => $keep->id, 'organization' => 'Parti B']],
    ]);

    $log = $figure->activityLogs()->where('action', ActivityAction::Edited)->sole();
    expect($figure->affiliations()->pluck('id')->all())->toBe([$keep->id])
        ->and($log->after)->toHaveKeys(['description', 'affiliations'])
        ->and($log->after)->not->toHaveKey('name');
});
