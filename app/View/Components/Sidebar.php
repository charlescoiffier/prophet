<?php

namespace App\View\Components;

use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\Verdict;
use App\View\Appearance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Barre latérale des vues, personnalités et thèmes (docs/maquettes/index.html).
 */
class Sidebar extends Component
{
    /** Nombre de personnalités, et de thèmes, listés ; les autres sont sur les pages d'index. */
    public const LIMIT = 12;

    /** @var array<string, int> */
    public array $counts;

    /** @var array<string, int> nombre de verdicts validés par tonalité (clés de Appearance::VERDICT_TONES) */
    public array $toneCounts;

    /** @var Collection<int, PublicFigure> */
    public Collection $figures;

    /** @var Collection<int, Theme> */
    public Collection $themes;

    /** Personnalités qui ont au moins une carte publique, listées ou non. */
    public int $figuresTotal;

    /** Thèmes qui ont au moins une carte publique, listés ou non. */
    public int $themesTotal;

    public function __construct()
    {
        $this->counts = [
            'all' => Card::visible()->count(),
            'awaiting' => Card::awaitingVerdict()->count(),
            'upcoming' => Card::where('status', CardStatus::Published)->whereDate('deadline', '>=', today())->count(),
            'review' => Card::where('status', CardStatus::VerdictProposed)->count(),
            'closed' => Card::where('status', CardStatus::Closed)->count(),
        ];

        // Verdicts validés par tonalité (tenues ou justes, partielles…), en une requête.
        $values = Verdict::where('status', VerdictStatus::Approved)
            ->toBase()
            ->selectRaw('value, count(*) as total')
            ->groupBy('value')
            ->pluck('total', 'value');

        $this->toneCounts = collect(Appearance::VERDICT_TONES)
            ->map(fn (array $tone) => (int) collect(VerdictValue::withTone($tone['tone']))
                ->sum(fn (VerdictValue $value) => $values[$value->value] ?? 0))
            ->all();

        // Les personnalités et thèmes qui ont au moins une carte publique : les plus fournis d'abord,
        // puis ceux dont une carte a été modifiée le plus récemment.
        $this->figures = $this->mostActive(PublicFigure::query());
        $this->themes = $this->mostActive(Theme::query());
        $this->figuresTotal = PublicFigure::whereHas('cards', fn ($query) => $query->visible())->count();
        $this->themesTotal = Theme::whereHas('cards', fn ($query) => $query->visible())->count();
    }

    /**
     * @template TModel of PublicFigure|Theme
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    private function mostActive(Builder $query): Collection
    {
        $visible = ['cards' => fn ($query) => $query->visible()];

        return $query->whereHas('cards', fn ($query) => $query->visible())
            ->withCount($visible)
            ->withMax($visible, 'updated_at')
            ->orderByDesc('cards_count')
            ->orderByDesc('cards_max_updated_at')
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }

    public function render(): View
    {
        return view('components.sidebar');
    }
}
