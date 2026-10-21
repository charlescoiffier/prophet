<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SavePublicFigureRequest;
use App\Http\Resources\V1\PublicFigureResource;
use App\Models\PublicFigure;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Personnalités', weight: 3)]
class PublicFigureController extends Controller
{
    /**
     * Lister les personnalités
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $figures = PublicFigure::query()
            ->with('category')
            ->withCount(['cards' => fn ($query) => $query->visible()])
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where('name', 'ilike', '%'.$request->string('q').'%'))
            ->alphabetical()
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return PublicFigureResource::collection($figures);
    }

    /**
     * Afficher une personnalité
     *
     * Avec ses affiliations et son bilan : nombre de verdicts validés par type de carte et par valeur
     * (des décomptes, jamais des pourcentages).
     *
     * @unauthenticated
     */
    public function show(PublicFigure $publicFigure): JsonResponse
    {
        $publicFigure->load(['affiliations', 'category'])->loadCount(['cards' => fn ($query) => $query->visible()]);

        return PublicFigureResource::make($publicFigure)
            ->additional(['record' => $publicFigure->record()])
            ->response();
    }

    /**
     * Créer une personnalité
     *
     * Réservé aux modérateurs.
     */
    public function store(SavePublicFigureRequest $request, SavePublicFigure $savePublicFigure): JsonResponse
    {
        $figure = $savePublicFigure->handle(null, $request->user(), $request->toActionData());

        return PublicFigureResource::make($figure->load(['affiliations', 'category']))->response()->setStatusCode(201);
    }

    /**
     * Corriger une personnalité
     *
     * Réservé aux modérateurs. « affiliations », si présent, remplace toute la liste.
     */
    public function update(SavePublicFigureRequest $request, PublicFigure $publicFigure, SavePublicFigure $savePublicFigure): PublicFigureResource
    {
        $figure = $savePublicFigure->handle($publicFigure, $request->user(), [
            'name' => $publicFigure->name,
            ...$request->toActionData(),
        ]);

        return PublicFigureResource::make($figure->load(['affiliations', 'category']));
    }
}
