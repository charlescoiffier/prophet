<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Verdicts\ProposeVerdict;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProposeVerdictRequest;
use App\Http\Resources\V1\VerdictResource;
use App\Models\Card;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Verdicts', weight: 2)]
class CardVerdictController extends Controller
{
    /**
     * Proposer un verdict
     *
     * Membre à l'adresse confirmée, au moins une source (règle 3). Une seule proposition en
     * attente par carte (règle 5) : 403 si la carte n'accepte pas de proposition.
     */
    public function store(ProposeVerdictRequest $request, Card $card, ProposeVerdict $proposeVerdict): JsonResponse
    {
        $verdict = $proposeVerdict->handle($card, $request->user(), $request->validated());

        return VerdictResource::make($verdict->load(['sources', 'proposer']))
            ->response()
            ->setStatusCode(201);
    }
}
