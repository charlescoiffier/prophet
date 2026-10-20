<?php

namespace App\Livewire;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Grille de cartes groupées par section (docs/maquettes/index.html).
 *
 * Sert l'accueil, les vues de la barre latérale, la page d'une personnalité,
 * celle d'un thème et la recherche.
 */
class Board extends Component
{
    /** Vues de la barre latérale. */
    public const VIEWS = ['all', 'awaiting', 'upcoming', 'closed', 'search'];

    public string $view = 'all';

    public ?PublicFigure $figure = null;

    public ?Theme $theme = null;

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $q = '';

    /** Cartes affichées par section ; « Afficher plus » l'augmente. */
    public int $limit = 12;

    public function mount(?string $view = null, ?PublicFigure $figure = null, ?Theme $theme = null): void
    {
        $view ??= 'all';
        abort_unless(in_array($view, self::VIEWS, true), 404);

        $this->view = $view;
        $this->figure = $figure;
        $this->theme = $theme;
        $this->limit = $view === 'all' && $figure === null && $theme === null ? 12 : 48;
    }

    public function showMore(): void
    {
        $this->limit += 48;
    }

    public function render(): View
    {
        return view('livewire.board', [
            'heading' => $this->title(),
            'sections' => $this->sections(),
            'record' => $this->figure?->record(),
            'currentAffiliation' => $this->figure?->affiliationOn(now()),
        ])
            ->title($this->title())
            ->layoutData(['description' => $this->description()]);
    }

    /**
     * @return list<array{key: string, title: string, cards: Collection<int, Card>, total: int}>
     */
    private function sections(): array
    {
        if ($this->view === 'search' && trim($this->q) === '') {
            return [];
        }

        $definitions = [
            'awaiting' => ['Échéance dépassée, verdict attendu', fn (Builder $query) => $query->awaitingVerdict()->orderBy('deadline')],
            'review' => ['Verdict en relecture', fn (Builder $query) => $query->where('status', CardStatus::VerdictProposed)->orderBy('deadline')],
            'upcoming' => ['Prochaines échéances', fn (Builder $query) => $query
                ->where('status', CardStatus::Published)
                ->where(fn (Builder $deadline) => $deadline->whereNull('deadline')->orWhereDate('deadline', '>=', today()))
                ->orderByRaw('deadline asc nulls last')],
            'closed' => ['Tranchées récemment', fn (Builder $query) => $query->where('status', CardStatus::Closed)->latest('updated_at')],
        ];

        $keys = match ($this->view) {
            'awaiting' => ['awaiting'],
            'upcoming' => ['upcoming'],
            'closed' => ['closed'],
            default => array_keys($definitions),
        };

        return collect($keys)
            ->map(function (string $key) use ($definitions) {
                [$title, $scope] = $definitions[$key];
                $query = $scope($this->baseQuery());

                return [
                    'key' => $key,
                    'title' => $title,
                    'total' => (clone $query)->count(),
                    'cards' => $query->orderBy('id')->limit($this->limit)->get(),
                ];
            })
            ->filter(fn (array $section) => $section['total'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return Builder<Card>
     */
    private function baseQuery(): Builder
    {
        return Card::query()
            ->visible()
            ->with(['publicFigure', 'themes', 'approvedVerdict'])
            ->when($this->figure, fn (Builder $query) => $query->where('public_figure_id', $this->figure->getKey()))
            ->when($this->theme, fn (Builder $query) => $query->whereHas('themes', fn (Builder $theme) => $theme->whereKey($this->theme->getKey())))
            ->when(CardType::tryFrom($this->type), fn (Builder $query, CardType $type) => $query->where('type', $type))
            ->when(trim($this->q) !== '', fn (Builder $query) => $query->whereRaw(
                "to_tsvector('french', quote || ' ' || coalesce(context, '')) @@ websearch_to_tsquery('french', ?)",
                [trim($this->q)],
            ));
    }

    private function title(): string
    {
        return match (true) {
            $this->figure !== null => $this->figure->name,
            $this->theme !== null => '# '.$this->theme->name,
            default => match ($this->view) {
                'awaiting' => 'Verdict attendu',
                'upcoming' => 'Échéances à venir',
                'closed' => 'Tranchées',
                'search' => 'Rechercher',
                default => 'Toutes les cartes',
            },
        };
    }

    private function description(): ?string
    {
        return match (true) {
            $this->figure !== null => "Promesses et prédictions de {$this->figure->name}, citées, datées, sourcées et tranchées.",
            $this->theme !== null => "Promesses et prédictions sur le thème « {$this->theme->name} ».",
            default => null,
        };
    }
}
