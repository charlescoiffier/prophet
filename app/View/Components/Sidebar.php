<?php

namespace App\View\Components;

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
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
            'closed' => Card::where('status', CardStatus::Closed)->count(),
        ];

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
