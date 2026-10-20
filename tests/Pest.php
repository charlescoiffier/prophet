<?php

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Support\AltchaGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Les tests « Feature » tournent sur PostgreSQL (base prophet_test, voir phpunit.xml) :
| plusieurs règles métier y sont aussi posées en contraintes et index partiels.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Anti-robot
|--------------------------------------------------------------------------
|
| Résout un vrai défi ALTCHA, comme le ferait le navigateur (coût réduit dans phpunit.xml).
|
*/

function altchaSolution(): string
{
    $challenge = app(AltchaGuard::class)->challenge();
    $solution = (new Altcha)->solveChallenge(new SolveChallengeOptions(
        algorithm: new Pbkdf2,
        challenge: $challenge,
    ));

    return (new Payload($challenge, $solution))->toBase64();
}
