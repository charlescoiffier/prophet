<?php

use App\Actions\PublicFigures\SavePublicFigure;
use App\Enums\ActivityAction;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Models\User;
use App\Support\Organizations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        'wikidata_id' => 'Q4242',
        'affiliations' => [['id' => $keep->id, 'organization' => 'Parti B']],
    ]);

    $log = $figure->activityLogs()->where('action', ActivityAction::Edited)->sole();
    expect($figure->affiliations()->pluck('id')->all())->toBe([$keep->id])
        ->and($log->after)->toHaveKeys(['wikidata_id', 'affiliations'])
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

/**
 * @return array<string, mixed>
 */
function portraitSettings(): array
{
    return [
        'version' => 1,
        'crop' => ['x' => 0, 'y' => 0, 'size' => 800],
        'cuts' => [],
        'brightness' => 0,
        'contrast' => 0,
        'threshold' => 128,
        'halo' => ['enabled' => false, 'width' => 8],
        'palette' => ['dark' => '#20212B', 'light' => '#D9D8D2', 'halo' => '#D9D8D2'],
    ];
}

/**
 * @param  array<string, mixed>  $photo
 */
function savePhoto(PublicFigure $figure, array $photo): PublicFigure
{
    return app(SavePublicFigure::class)->handle($figure, User::factory()->moderator()->create(), [
        'name' => $figure->name, 'category_id' => $figure->category_id, ...$photo,
    ]);
}

it('enregistre l’image traitée, l’original et les réglages', function () {
    $figure = savePhoto(PublicFigure::factory()->create(), [
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => portraitSettings(),
    ]);

    expect($figure->refresh()->hasProcessedPhoto())->toBeTrue()
        ->and($figure->photo_settings['threshold'])->toBe(128);
});

it('efface l’ancien original quand la photo est refaite à partir d’un nouveau fichier', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/originals/a.jpg', 'x');
    Storage::disk('public')->put('portraits/originals/b.jpg', 'x');
    $figure = PublicFigure::factory()->create(['photo_path' => 'portraits/p1.png', 'photo_original_path' => 'portraits/originals/a.jpg', 'photo_settings' => portraitSettings()]);

    savePhoto($figure, ['photo_path' => 'portraits/p2.png', 'photo_original_path' => 'portraits/originals/b.jpg', 'photo_settings' => portraitSettings()]);

    Storage::disk('public')->assertMissing('portraits/originals/a.jpg');
    Storage::disk('public')->assertExists('portraits/originals/b.jpg');
});

it('garde l’original quand on retouche seulement les réglages', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/originals/a.jpg', 'x');
    Storage::disk('public')->put('portraits/p1.png', 'x');
    $figure = PublicFigure::factory()->create(['photo_path' => 'portraits/p1.png', 'photo_original_path' => 'portraits/originals/a.jpg', 'photo_settings' => portraitSettings()]);

    savePhoto($figure, ['photo_path' => 'portraits/p2.png', 'photo_original_path' => 'portraits/originals/a.jpg', 'photo_settings' => [...portraitSettings(), 'threshold' => 90]]);

    Storage::disk('public')->assertExists('portraits/originals/a.jpg');
    Storage::disk('public')->assertMissing('portraits/p1.png');
});

it('retire la photo et ses deux fichiers', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/originals/a.jpg', 'x');
    Storage::disk('public')->put('portraits/p1.png', 'x');
    $figure = PublicFigure::factory()->create(['photo_path' => 'portraits/p1.png', 'photo_original_path' => 'portraits/originals/a.jpg', 'photo_settings' => portraitSettings()]);

    savePhoto($figure, ['photo_path' => null, 'photo_original_path' => null, 'photo_settings' => null]);

    expect($figure->refresh())->photo_path->toBeNull()->photo_original_path->toBeNull()->photo_settings->toBeNull();
    Storage::disk('public')->assertMissing('portraits/originals/a.jpg');
    Storage::disk('public')->assertMissing('portraits/p1.png');
});

it('refuse un original ou des réglages sans les deux autres', function (array $photo) {
    $figure = PublicFigure::factory()->create();

    expect(fn () => savePhoto($figure, $photo))->toThrow(ValidationException::class);
    expect($figure->refresh()->photo_path)->toBeNull();
})->with([
    'original seul' => [['photo_original_path' => 'portraits/originals/o.jpg']],
    'réglages seuls' => [['photo_settings' => ['version' => 1]]],
    'image et réglages sans original' => [['photo_path' => 'portraits/p.png', 'photo_settings' => ['version' => 1]]],
]);

it('n’inscrit ni l’original ni les réglages au journal', function () {
    $figure = savePhoto(PublicFigure::factory()->create(), [
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => portraitSettings(),
    ]);

    $log = $figure->activityLogs()->where('action', ActivityAction::Edited)->sole();
    expect(array_keys($log->after))->toBe(['photo_path']);
});
