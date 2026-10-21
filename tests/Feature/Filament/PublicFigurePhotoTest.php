<?php

use App\Filament\Resources\PublicFigures\Pages\EditPublicFigure;
use App\Models\PublicFigure;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('enregistre une photo téléversée depuis la fiche d’une personnalité', function () {
    Storage::fake('public');
    $figure = PublicFigure::factory()->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(EditPublicFigure::class, ['record' => $figure->getRouteKey()])
        ->fillForm(['photo_path' => UploadedFile::fake()->image('portrait.png', 800, 800)])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh()->photo_path)->toStartWith('portraits/');
    Storage::disk('public')->assertExists($figure->photo_path);
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

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(EditPublicFigure::class, ['record' => $figure->getRouteKey()])
        ->fillForm(['photo_path' => UploadedFile::fake()->image('portrait.png', 800, 800)])
        ->assertSchemaStateSet(['photo_url' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($figure->refresh())->photo_url->toBeNull()->photo_path->toStartWith('portraits/');
});
