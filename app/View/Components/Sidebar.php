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
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Barre latérale des vues, personnalités et thèmes (docs/maquettes/index.html).
 */
class Sidebar extends Component
{
    /** @var array<string, int> */
    public array $counts;

    /** @var array<string, int> nombre de verdicts validés par tonalité (clés de Appearance::VERDICT_TONES) */
    public array $toneCounts;

    /** @var Collection<int, PublicFigure> */
    public Collection $figures;

    /** @var Collection<int, Theme> */
    public Collection $themes;

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

        // Les personnalités et thèmes qui ont au moins une carte publique, les plus fournis d'abord.
        $this->figures = PublicFigure::whereHas('cards', fn ($query) => $query->visible())
            ->withCount(['cards' => fn ($query) => $query->visible()])
            ->orderByDesc('cards_count')->orderBy('name')
            ->limit(12)
            ->get();

        $this->themes = Theme::whereHas('cards', fn ($query) => $query->visible())
            ->withCount(['cards' => fn ($query) => $query->visible()])
            ->orderByDesc('cards_count')->orderBy('name')
            ->limit(12)
            ->get();
    }

    public function render(): View
    {
        return view('components.sidebar');
    }
}
