<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Verdicts\UpdateVerdict;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateVerdictRequest;
use App\Http\Resources\V1\VerdictResource;
use App\Models\Verdict;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Verdicts', weight: 2)]
class VerdictController extends Controller
{
    /**
     * Afficher un verdict
     *
     * Un verdict validé est public ; une proposition n'est visible que de son auteur et des modérateurs.
     *
     * @unauthenticated
     */
    public function show(Request $request, Verdict $verdict): VerdictResource
    {
        abort_unless(Gate::forUser($request->user('sanctum'))->allows('view', $verdict), 404);

        return VerdictResource::make($verdict->load(['sources', 'proposer', 'decider']));
    }

    /**
     * Corriger un verdict proposé
     *
     * Réservé aux modérateurs, avant validation, jamais sur sa propre proposition.
     */
    public function update(UpdateVerdictRequest $request, Verdict $verdict, UpdateVerdict $updateVerdict): VerdictResource
    {
        $verdict = $updateVerdict->handle($verdict, $request->user(), $request->validated());

        return VerdictResource::make($verdict->load(['sources', 'proposer', 'decider']));
    }
}
