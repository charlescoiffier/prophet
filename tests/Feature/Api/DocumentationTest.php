<?php

it('publie la documentation OpenAPI de toutes les routes v1', function () {
    $document = $this->getJson('/docs/api.json')->assertOk()->json();

    $operations = collect($document['paths'])->flatMap(fn (array $path) => array_values($path));

    expect($document['info']['version'])->toBe('1.0.0')
        ->and($operations)->toHaveCount(26)
        ->and($operations->pluck('tags')->flatten()->unique()->sort()->values()->all())
        ->toBe(['Cartes', 'Comptes', 'Modération', 'Personnalités', 'Verdicts']);
});
