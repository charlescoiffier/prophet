<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reports\ReportCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportCardRequest;
use App\Models\Card;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Cartes', weight: 1)]
class CardReportController extends Controller
{
    /**
     * Signaler une erreur
     *
     * Ouvert à tous sur une carte publique. Le texte n'est lu que par les modérateurs.
     *
     * @unauthenticated
     */
    public function store(ReportCardRequest $request, Card $card, ReportCard $reportCard): JsonResponse
    {
        $reportCard->handle($card, $request->user('sanctum'), $request->validated('reason'), $request->validated('email'));

        return response()->json(['message' => __('api.report_received')], 201);
    }
}
