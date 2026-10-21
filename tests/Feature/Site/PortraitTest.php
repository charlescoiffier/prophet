<?php

use App\Models\PublicFigure;

it('affiche une photo traitée telle quelle', function () {
    $figure = PublicFigure::factory()->create([
        'photo_path' => 'portraits/p.png',
        'photo_original_path' => 'portraits/originals/o.jpg',
        'photo_settings' => ['version' => 1],
    ]);

    $this->blade('<x-portrait :figure="$figure" />', ['figure' => $figure])
        ->assertSee($figure->photoUrl())
        ->assertDontSee('grayscale')
        ->assertDontSee('mix-blend-multiply');
});

it('garde le filtre photocopie pour une photo non traitée', function (array $photo) {
    $figure = PublicFigure::factory()->create($photo);

    $this->blade('<x-portrait :figure="$figure" />', ['figure' => $figure])
        ->assertSee('grayscale');
})->with([
    'photo par lien' => [['photo_url' => 'https://example.org/portrait.jpg']],
    'photo téléversée avant l’atelier' => [['photo_path' => 'portraits/ancienne.jpg']],
]);
