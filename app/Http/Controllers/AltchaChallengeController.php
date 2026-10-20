<?php

namespace App\Http\Controllers;

use App\Support\AltchaGuard;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Comptes', weight: 4)]
class AltchaChallengeController extends Controller
{
    /**
     * Défi anti-robot
     *
     * Défi ALTCHA (preuve de travail, sans cookie) à résoudre avant de proposer une carte,
     * signaler une erreur ou créer un compte sans jeton. La solution, encodée en base64,
     * se passe dans le champ « altcha ». Elle ne sert qu'une fois.
     *
     * @unauthenticated
     */
    public function __invoke(AltchaGuard $altchaGuard): JsonResponse
    {
        return response()->json($altchaGuard->challenge()->toArray())
            ->header('Cache-Control', 'no-store');
    }
}
