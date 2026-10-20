<?php

namespace App\Support;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Crée et vérifie les défis ALTCHA (anti-robot sans cookie).
 *
 * Une solution ne sert qu'une fois : sa signature est mémorisée jusqu'à l'expiration du défi.
 */
class AltchaGuard
{
    private Altcha $altcha;

    private Pbkdf2 $algorithm;

    public function __construct()
    {
        $secret = config('altcha.hmac_key') ?: hash_hmac('sha256', 'altcha', (string) config('app.key'));

        $this->altcha = new Altcha(
            hmacSignatureSecret: $secret,
            hmacKeySignatureSecret: hash_hmac('sha256', 'altcha-key', $secret),
        );
        $this->algorithm = new Pbkdf2;
    }

    public function challenge(): Challenge
    {
        return $this->altcha->createChallenge(new CreateChallengeOptions(
            algorithm: $this->algorithm,
            cost: config('altcha.cost'),
            counter: random_int(config('altcha.counter_min'), config('altcha.counter_max')),
            expiresAt: time() + config('altcha.expires'),
        ));
    }

    public function verify(?string $payload): bool
    {
        if (blank($payload)) {
            return false;
        }

        try {
            $result = $this->altcha->verifySolution(new VerifySolutionOptions(
                algorithm: $this->algorithm,
                payload: $payload,
            ));
        } catch (InvalidArgumentException) {
            return false;
        }

        if (! $result->verified) {
            return false;
        }

        // Rejeu : la même solution ne passe qu'une fois.
        $signature = json_decode((string) base64_decode($payload, true), true)['challenge']['signature'] ?? $payload;

        return Cache::add('altcha:used:'.hash('sha256', (string) $signature), true, config('altcha.expires'));
    }
}
