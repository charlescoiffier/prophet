<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cards\PublishCard;
use App\Actions\Cards\RejectCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecisionRequest;
use App\Http\Resources\V1\CardResource;
use App\Models\Card;
use Dedoc\Scramble\Attributes\Group;

#[Group('Modération', weight: 5)]
class CardDecisionController extends Controller
{
    /**
     * Publier ou refuser une carte
     *
     * « approve » publie la carte, « reject » la refuse avec un motif. Réservé aux modérateurs ;
     * personne ne décide sur sa propre carte, sauf l'admin pour la publier (règle 4).
     */
    public function __invoke(DecisionRequest $request, Card $card, PublishCard $publishCard, RejectCard $rejectCard): CardResource
    {
        $card = $request->approves()
            ? $publishCard->handle($card, $request->user())
            : $rejectCard->handle($card, $request->user(), (string) $request->validated('reason'));

        return CardResource::make($card);
    }
}
