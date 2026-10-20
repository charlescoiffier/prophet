<?php

use App\Support\AltchaGuard;

it('accepte une solution valide, une seule fois', function () {
    $guard = app(AltchaGuard::class);
    $payload = altchaSolution();

    expect($guard->verify($payload))->toBeTrue()
        ->and($guard->verify($payload))->toBeFalse();
});

it('refuse une solution falsifiée, vide ou illisible', function (string $payload) {
    expect(app(AltchaGuard::class)->verify($payload))->toBeFalse();
})->with([
    'vide' => '',
    'illisible' => 'pas du base64 !',
    'clé dérivée falsifiée' => function () {
        $payload = json_decode(base64_decode(altchaSolution()), true);
        $payload['solution']['derivedKey'] = str_repeat('0', strlen($payload['solution']['derivedKey']));

        return base64_encode(json_encode($payload));
    },
]);

it('refuse une solution expirée', function () {
    // La bibliothèque lit l'heure du système : on crée directement un défi déjà expiré.
    config(['altcha.expires' => -60]);
    $payload = altchaSolution();

    expect(app(AltchaGuard::class)->verify($payload))->toBeFalse();
});

it('sert un défi sans cookie ni cache', function (string $url) {
    $this->getJson($url)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonStructure(['parameters' => ['algorithm', 'cost', 'expiresAt', 'keyPrefix', 'salt', 'nonce'], 'signature'])
        ->assertJsonPath('parameters.algorithm', 'PBKDF2/SHA-256');
})->with(['/altcha', '/api/v1/altcha']);
