<?php

use App\Filament\Resources\PublicFigures\Pages\EditPublicFigure;
use App\Models\PublicFigure;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function studioSettings(int $threshold = 128): array
{
    return [
        'version' => 1,
        'crop' => ['x' => 0, 'y' => 0, 'size' => 900],
        'cuts' => [['points' => [[0, 540], [300, 100], [600, 520]], 'discard' => [300, 20]]],
        'brightness' => 0,
        'contrast' => 0,
        'threshold' => $threshold,
        'halo' => ['enabled' => false, 'width' => 8],
        'palette' => ['dark' => '#20212B', 'light' => '#D9D8D2', 'halo' => '#D9D8D2'],
    ];
}

function editFigure(PublicFigure $figure): Testable
{
    return Livewire::actingAs(User::factory()->moderator()->create())
        ->test(EditPublicFigure::class, ['record' => $figure->getRouteKey()]);
}

it('enregistre l’image, l’original et les réglages remis par l’atelier', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create();

    editFigure($figure)
        ->set('data.photo.image', UploadedFile::fake()->image('portrait.png', 600, 600))
        ->set('data.photo.original', UploadedFile::fake()->image('o.jpg', 1200, 900))
        ->set('data.photo.settings', studioSettings())
        ->call('save')
        ->assertHasNoFormErrors();

    $figure->refresh();
    expect($figure->photo_path)->toStartWith('portraits/')->toEndWith('.png')
        ->and($figure->photo_original_path)->toStartWith('portraits/originals/')
        ->and($figure->photo_settings['version'])->toBe(1);
    Storage::disk('public')->assertExists([$figure->photo_path, $figure->photo_original_path]);
});

it('retouche sans changer l’original', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/originals/o.jpg', 'x');
    $figure = PublicFigure::factory()->create([
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => studioSettings(),
    ]);

    editFigure($figure)
        ->set('data.photo.image', UploadedFile::fake()->image('portrait.png', 600, 600))
        ->set('data.photo.settings', studioSettings(threshold: 90))
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())
        ->photo_original_path->toBe('portraits/originals/o.jpg')
        ->photo_path->not->toBe('portraits/p.png')
        ->and($figure->photo_settings['threshold'])->toBe(90);
});

it('ne touche pas à la photo si l’atelier est fermé sans valider', function () {
    $figure = PublicFigure::factory()->create([
        'photo_url' => 'https://example.org/lien.jpg',
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => studioSettings(),
    ]);

    editFigure($figure)
        ->fillForm(['description' => 'Députée'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())
        ->photo_path->toBe('portraits/p.png')
        ->photo_original_path->toBe('portraits/originals/o.jpg')
        ->photo_url->toBe('https://example.org/lien.jpg');
});

it('retire la photo', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create([
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => studioSettings(),
    ]);

    editFigure($figure)
        ->set('data.photo', ['path' => null, 'original_path' => null, 'settings' => null, 'image' => null, 'original' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())->photo_path->toBeNull()->photo_original_path->toBeNull()->photo_settings->toBeNull();
});

it('refuse un fichier qui n’est pas une image', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create();

    editFigure($figure)
        ->set('data.photo.image', UploadedFile::fake()->create('portrait.php', 10, 'text/x-php'))
        ->set('data.photo.original', UploadedFile::fake()->image('o.jpg', 1200, 900))
        ->set('data.photo.settings', studioSettings())
        ->call('save')
        ->assertHasFormErrors(['photo']);

    expect($figure->refresh()->photo_path)->toBeNull();
});

it('refuse un lien vers une page web au lieu du fichier image', function (string $url, bool $accepted) {
    $figure = PublicFigure::factory()->create();

    $test = Livewire::actingAs(User::factory()->moderator()->create())
        ->test(EditPublicFigure::class, ['record' => $figure->getRouteKey()])
        ->fillForm(['photo_url' => $url])
        ->call('save');

    $accepted ? $test->assertHasNoFormErrors() : $test->assertHasFormErrors(['photo_url' => 'regex']);
})->with([
    'page Wikipédia' => ['https://fr.wikipedia.org/wiki/Exemple#/media/Fichier:Exemple.jpg', false],
    'page sans extension' => ['https://example.org/portrait', false],
    'fichier image' => ['https://upload.wikimedia.org/wikipedia/commons/a/ab/Exemple.jpg', true],
    'fichier image avec paramètres' => ['https://example.org/portrait.webp?width=600', true],
]);

it('remplace un ancien lien invalide quand on téléverse une photo', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create(['photo_url' => 'https://fr.wikipedia.org/wiki/Exemple#/media/Fichier:Exemple.jpg']);

    editFigure($figure)
        ->set('data.photo.original', UploadedFile::fake()->image('o.jpg', 1200, 900))
        ->set('data.photo.settings', studioSettings())
        ->set('data.photo.image', UploadedFile::fake()->image('portrait.png', 600, 600))
        ->assertSchemaStateSet(['photo_url' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())->photo_url->toBeNull()->photo_path->toStartWith('portraits/');
});

it('ne recopie pas la photo quand on enregistre à nouveau la même page', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create();

    $page = editFigure($figure)
        ->set('data.photo.image', UploadedFile::fake()->image('portrait.png', 600, 600))
        ->set('data.photo.original', UploadedFile::fake()->image('o.jpg', 1200, 900))
        ->set('data.photo.settings', studioSettings())
        ->call('save')
        ->assertHasNoFormErrors();
    $first = $figure->refresh()->only(['photo_path', 'photo_original_path']);

    $page->fillForm(['description' => 'Députée'])->call('save')->assertHasNoFormErrors();

    expect($figure->refresh()->only(['photo_path', 'photo_original_path']))->toBe($first)
        ->and(Storage::disk('public')->allFiles())->toHaveCount(2);
});

it('ignore un chemin de fichier envoyé par le navigateur', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portraits/autre.png', 'x');
    $figure = PublicFigure::factory()->create([
        'photo_path' => 'portraits/p.png', 'photo_original_path' => 'portraits/originals/o.jpg', 'photo_settings' => studioSettings(),
    ]);

    editFigure($figure)
        ->set('data.photo.path', 'portraits/autre.png')
        ->set('data.photo.original_path', 'portraits/autre.png')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())
        ->photo_path->toBe('portraits/p.png')
        ->photo_original_path->toBe('portraits/originals/o.jpg');
    Storage::disk('public')->assertExists('portraits/autre.png');
});
