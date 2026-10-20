<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CardStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CardResource;
use App\Models\Card;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

#[Group('Cartes', weight: 1)]
class DeadlineController extends Controller
{
    /**
     * Échéances
     *
     * « overdue » (par défaut) : échéance dépassée, verdict attendu, la plus ancienne d'abord.
     * « upcoming » : échéance dans les 90 prochains jours.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'window' => ['nullable', Rule::in(['overdue', 'upcoming'])],
        ]);

        $query = ($validated['window'] ?? 'overdue') === 'overdue'
            ? Card::awaitingVerdict()
            : Card::where('status', CardStatus::Published)->whereBetween('deadline', [today(), today()->addDays(90)]);

        return CardResource::collection(
            $query->with(['publicFigure', 'themes'])->orderBy('deadline')->orderBy('id')->paginate(20)->withQueryString()
        );
    }
}
