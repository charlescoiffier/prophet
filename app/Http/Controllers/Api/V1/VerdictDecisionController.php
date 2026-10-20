<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Verdicts\ApproveVerdict;
use App\Actions\Verdicts\RejectVerdict;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecisionRequest;
use App\Http\Resources\V1\VerdictResource;
use App\Models\Verdict;
use Dedoc\Scramble\Attributes\Group;

#[Group('Modération', weight: 5)]
class VerdictDecisionController extends Controller
{
    /**
     * Valider ou refuser un verdict
     *
     * « approve » valide le verdict et tranche la carte ; « reject » le refuse avec un motif et
     * la carte attend un nouveau verdict. Jamais sur sa propre proposition (règle 4).
     */
    public function __invoke(DecisionRequest $request, Verdict $verdict, ApproveVerdict $approveVerdict, RejectVerdict $rejectVerdict): VerdictResource
    {
        $verdict = $request->approves()
            ? $approveVerdict->handle($verdict, $request->user())
            : $rejectVerdict->handle($verdict, $request->user(), (string) $request->validated('reason'));

        return VerdictResource::make($verdict->load(['sources', 'proposer', 'decider']));
    }
}
