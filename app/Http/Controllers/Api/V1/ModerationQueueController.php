<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CardStatus;
use App\Enums\ReportStatus;
use App\Enums\VerdictStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CardResource;
use App\Http\Resources\V1\ReportResource;
use App\Http\Resources\V1\VerdictResource;
use App\Models\Card;
use App\Models\Report;
use App\Models\Verdict;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

#[Group('Modération', weight: 5)]
class ModerationQueueController extends Controller
{
    /**
     * File de modération
     *
     * Tout ce qui attend une décision : cartes à relire, verdicts à valider, signalements ouverts.
     * Réservé aux modérateurs ; « is_own » signale ses propres propositions.
     */
    public function __invoke(): JsonResponse
    {
        Gate::authorize('moderate');

        return response()->json([
            'cards' => CardResource::collection(
                Card::where('status', CardStatus::InModeration)->with(['publicFigure', 'themes', 'sources', 'author'])->oldest()->get()
            ),
            'verdicts' => VerdictResource::collection(
                Verdict::where('status', VerdictStatus::Proposed)->with(['sources', 'proposer'])->oldest()->get()
            ),
            'reports' => ReportResource::collection(
                Report::where('status', ReportStatus::Open)->with('user')->oldest()->get()
            ),
        ]);
    }
}
