<?php

use App\Actions\PublicFigures\SavePublicFigure;
use App\Enums\ActivityAction;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Models\User;
use App\Support\Organizations;
use Illuminate\Support\Facades\Storage;

it('crée la fiche avec un slug et ses affiliations', function () {
    $figure = app(SavePublicFigure::class)->handle(null, User::factory()->moderator()->create(), [
        'name' => 'Élise Démo',
        'category_id' => Category::where('slug', 'politique')->value('id'),
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
        'category_id' => Category::where('slug', 'politique')->value('id'),
        'affiliations' => [['organization' => 'Parti A'], ['organization' => 'Parti B']],
    ]);
    $keep = $figure->affiliations()->where('organization', 'Parti B')->sole();

    app(SavePublicFigure::class)->handle($figure, $moderator, [
        'name' => 'Élise Démo',
        'category_id' => Category::where('slug', 'politique')->value('id'),
        'description' => 'Députée',
        'affiliations' => [['id' => $keep->id, 'organization' => 'Parti B']],
    ]);

    $log = $figure->activityLogs()->where('action', ActivityAction::Edited)->sole();
    expect($figure->affiliations()->pluck('id')->all())->toBe([$keep->id])
        ->and($log->after)->toHaveKeys(['description', 'affiliations'])
        ->and($log->after)->not->toHaveKey('name');
});

it('reprend l’orthographe d’une organisation connue écrite autrement', function () {
    $moderator = User::factory()->moderator()->create();
    $category = Category::where('slug', 'politique')->value('id');
    app(SavePublicFigure::class)->handle(null, $moderator, ['name' => 'Une', 'category_id' => $category, 'affiliations' => [['organization' => 'Parti des Écologistes']]]);

    $figure = app(SavePublicFigure::class)->handle(null, $moderator, ['name' => 'Deux', 'category_id' => $category, 'affiliations' => [['organization' => 'parti des ecologistes ']]]);

    expect($figure->affiliations()->sole()->organization)->toBe('Parti des Écologistes')
        ->and(Organizations::known()->all())->toBe(['Parti des Écologistes']);
});

it('remplace la photo téléversée et efface l’ancien fichier', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/ancienne.jpg', 'x');
    $figure = PublicFigure::factory()->create(['photo_path' => 'portraits/ancienne.jpg']);

    app(SavePublicFigure::class)->handle($figure, User::factory()->moderator()->create(), [
        'name' => $figure->name, 'category_id' => $figure->category_id, 'photo_path' => 'portraits/nouvelle.jpg',
    ]);

    Storage::disk('public')->assertMissing('portraits/ancienne.jpg');
    expect($figure->refresh()->photoUrl())->toEndWith('/storage/portraits/nouvelle.jpg');
});
