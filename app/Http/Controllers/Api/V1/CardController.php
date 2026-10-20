<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cards\ProposeCard;
use App\Actions\Cards\SearchCards;
use App\Actions\Cards\UpdateCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListCardsRequest;
use App\Http\Requests\Api\V1\ProposeCardRequest;
use App\Http\Requests\Api\V1\UpdateCardRequest;
use App\Http\Resources\V1\CardResource;
use App\Models\Card;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Cartes', weight: 1)]
class CardController extends Controller
{
    /**
     * Lister les cartes publiques
     *
     * Cartes publiées, en attente de verdict ou tranchées. Filtrables et paginées.
     *
     * @unauthenticated
     */
    public function index(ListCardsRequest $request, SearchCards $searchCards): AnonymousResourceCollection
    {
        $cards = $searchCards->handle($request->validated())
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return CardResource::collection($cards);
    }

    /**
     * Afficher une carte
     *
     * Avec ses sources, son verdict validé et son historique public. Une carte non publique
     * n'est visible que de son auteur et des modérateurs (404 pour les autres).
     *
     * @unauthenticated
     */
    public function show(Request $request, Card $card): CardResource
    {
        abort_unless(Gate::forUser($request->user('sanctum'))->allows('view', $card), 404);

        $card->load([
            'publicFigure.affiliations',
            'themes',
            'author',
            'sources',
            'approvedVerdict.sources',
            'approvedVerdict.proposer',
            'approvedVerdict.decider',
            'activityLogs.actor',
        ]);

        return CardResource::make($card);
    }

    /**
     * Proposer une carte
     *
     * Avec un jeton (membre à l'adresse confirmée), la carte part en modération. Sans jeton,
     * l'adresse e-mail est obligatoire : la carte attend la confirmation par le lien envoyé (règle 1).
     *
     * @unauthenticated
     */
    public function store(ProposeCardRequest $request, ProposeCard $proposeCard): JsonResponse
    {
        $card = $proposeCard->handle($request->user('sanctum'), $request->validated());

        return CardResource::make($card->load(['publicFigure', 'themes', 'sources', 'author']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Modifier la rédaction d'une carte
     *
     * Réservé aux modérateurs, jamais sur sa propre carte (sauf l'admin). L'auteur est prévenu
     * et le changement inscrit à l'historique public.
     */
    public function update(UpdateCardRequest $request, Card $card, UpdateCard $updateCard): CardResource
    {
        $card = $updateCard->handle($card, $request->user(), $request->validated());

        return CardResource::make($card->load(['publicFigure', 'themes', 'sources', 'author']));
    }
}
