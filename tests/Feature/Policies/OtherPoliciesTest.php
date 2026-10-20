<?php

use App\Enums\ReportStatus;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Report;
use App\Models\Theme;
use App\Models\User;

it('réserve la gestion des thèmes à l’administrateur', function () {
    $theme = Theme::factory()->create();

    expect(User::factory()->admin()->create()->can('update', $theme))->toBeTrue()
        ->and(User::factory()->moderator()->create()->can('update', $theme))->toBeFalse();
});

it('laisse un modérateur créer et corriger une personnalité', function () {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->can('create', PublicFigure::class))->toBeTrue()
        ->and($moderator->can('update', PublicFigure::factory()->create()))->toBeTrue()
        ->and(User::factory()->create()->can('create', PublicFigure::class))->toBeFalse();
});

it('ne supprime une personnalité que si elle n’a aucune carte, et seulement par l’admin', function () {
    $admin = User::factory()->admin()->create();
    $withCard = Card::factory()->create()->publicFigure;

    expect($admin->can('delete', PublicFigure::factory()->create()))->toBeTrue()
        ->and($admin->can('delete', $withCard))->toBeFalse()
        ->and(User::factory()->moderator()->create()->can('delete', PublicFigure::factory()->create()))->toBeFalse();
});

it('ne traite qu’un signalement ouvert', function () {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->can('handle', Report::factory()->create()))->toBeTrue()
        ->and($moderator->can('handle', Report::factory()->create(['status' => ReportStatus::Resolved])))->toBeFalse()
        ->and(User::factory()->create()->can('handle', Report::factory()->create()))->toBeFalse();
});

it('laisse l’admin changer le rôle des autres, pas le sien', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('changeRole', User::factory()->create()))->toBeTrue()
        ->and($admin->can('changeRole', $admin))->toBeFalse()
        ->and(User::factory()->moderator()->create()->can('changeRole', User::factory()->create()))->toBeFalse();
});

it('ouvre le panneau de modération aux seuls modérateurs à l’adresse confirmée', function () {
    $panel = Filament\Facades\Filament::getPanel('moderation');

    expect(User::factory()->moderator()->create()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->admin()->create()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->create()->canAccessPanel($panel))->toBeFalse()
        ->and(User::factory()->moderator()->unverified()->create()->canAccessPanel($panel))->toBeFalse();
});
