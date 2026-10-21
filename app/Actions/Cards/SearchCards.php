<?php

namespace App\Actions\Cards;

use App\Enums\CardStatus;
use App\Models\Card;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste publique des cartes, filtrée et triée. Sert à l'API et au site public.
 */
class SearchCards
{
    /** Tris acceptés ; un « - » en tête inverse l'ordre. */
    public const SORTS = ['published_at', 'deadline', 'stated_on'];

    /**
     * @param  array{q?: string, public_figure?: string, type?: string, theme?: string, status?: string, deadline_before?: string, deadline_after?: string, sort?: string}  $filters
     * @return Builder<Card>
     */
    public function handle(array $filters): Builder
    {
        $query = Card::query()
            ->with(['publicFigure', 'themes', 'approvedVerdict', 'author'])
            ->visible();

        if (filled($filters['q'] ?? null)) {
            $query->matching($filters['q']);
        }

        if (filled($filters['public_figure'] ?? null)) {
            $query->whereHas('publicFigure', fn (Builder $figure) => $figure->where('slug', $filters['public_figure']));
        }

        if (filled($filters['theme'] ?? null)) {
            $query->whereHas('themes', fn (Builder $theme) => $theme->where('slug', $filters['theme']));
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        if (filled($filters['status'] ?? null) && CardStatus::from($filters['status'])->isPublic()) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['deadline_before'] ?? null)) {
            $query->whereDate('deadline', '<=', $filters['deadline_before']);
        }

        if (filled($filters['deadline_after'] ?? null)) {
            $query->whereDate('deadline', '>=', $filters['deadline_after']);
        }

        $sort = $filters['sort'] ?? '-published_at';
        $column = ltrim($sort, '-');

        return $query
            ->orderBy(in_array($column, self::SORTS, true) ? $column : 'published_at', str_starts_with($sort, '-') ? 'desc' : 'asc')
            ->orderBy('id');
    }
}
