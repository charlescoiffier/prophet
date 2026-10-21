<?php

namespace App\Livewire;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\VerdictValue;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\View\Appearance;
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
    public const VIEWS = ['all', 'awaiting', 'upcoming', 'review', 'closed', 'verdict', 'search'];

    /**
     * Sections : titre et phrase d'accompagnement (maquette éditoriale).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const SECTIONS = [
        'awaiting' => ['Échéance dépassée, verdict attendu', 'L’échéance est passée. Aux faits de parler.'],
        'review' => ['Verdict en relecture', 'Les faits sont là. Le verdict se vérifie.'],
        'upcoming' => ['Prochaines échéances', 'Le rendez-vous est pris.'],
        'closed' => ['Verdicts récents', 'Quand la parole rencontre le réel.'],
    ];

    public string $view = 'all';

    public ?PublicFigure $figure = null;

    public ?Theme $theme = null;

    /** Tonalité de verdict de la vue « verdict » (clé de Appearance::VERDICT_TONES). */
    public ?string $tone = null;

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $q = '';

    /** Champ de recherche ouvert dans la barre d'outils (il filtre les cartes de la vue en direct). */
    public bool $searching = false;

    /**
     * Filtres à choix multiples, combinés entre eux : états (sections de SECTIONS), tonalités de
     * verdict (clés de Appearance::VERDICT_TONES), personnalités et thèmes (slugs).
     *
     * @var list<string>
     */
    #[Url(as: 'etats', except: [])]
    public array $states = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $verdicts = [];

    /** @var list<string> */
    #[Url(as: 'personnalites', except: [])]
    public array $figures = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $themes = [];

    /** Rangée des filtres ouverte dans la barre d'outils. */
    public bool $filtering = false;

    /** Libellés des états proposés au filtre, dans l'ordre de la barre latérale. */
    public const STATE_LABELS = [
        'awaiting' => 'Verdict attendu',
        'upcoming' => 'Échéances à venir',
        'review' => 'En relecture',
        'closed' => 'Tranchées',
    ];

    /** Cartes affichées par section ; « Afficher plus » l'augmente. */
    public int $limit = 12;

    public function mount(?string $view = null, ?PublicFigure $figure = null, ?Theme $theme = null, ?string $tone = null): void
    {
        $view ??= 'all';
        abort_unless(in_array($view, self::VIEWS, true), 404);

        // /recherche : l'accueil, champ de recherche ouvert.
        if ($view === 'search') {
            $view = 'all';
            $this->searching = true;
        }

        $this->searching = $this->searching || trim($this->q) !== '';
        $this->filtering = $this->activeFilterCount() > 0;
        abort_if($view === 'verdict' && ! isset(Appearance::VERDICT_TONES[$tone]), 404);

        $this->view = $view;
        $this->tone = $tone;
        $this->figure = $figure;
        $this->theme = $theme;
        $this->limit = $view === 'all' && $figure === null && $theme === null ? 12 : 48;
    }

    public function closeSearch(): void
    {
        $this->q = '';
        $this->searching = false;
    }

    public function clearFilters(): void
    {
        $this->reset('states', 'verdicts', 'figures', 'themes');
    }

    public function activeFilterCount(): int
    {
        return count($this->states) + count($this->verdicts) + count($this->figures) + count($this->themes);
    }

    public function showMore(): void
    {
        $this->limit += 48;
    }

    public function render(): View
    {
        $counts = (clone $this->baseQuery(withType: false))
            ->reorder()
            ->toBase()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('livewire.board', [
            'heading' => $this->title(),
            'lead' => $this->lead(),
            'typeCounts' => [
                '' => (int) $counts->sum(),
                CardType::Promise->value => (int) ($counts[CardType::Promise->value] ?? 0),
                CardType::Prediction->value => (int) ($counts[CardType::Prediction->value] ?? 0),
            ],
            'sections' => $this->sections(),
            'filterOptions' => $this->filterOptions(),
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
        $scopes = [
            'awaiting' => fn (Builder $query) => $query->awaitingVerdict()->orderBy('deadline'),
            'review' => fn (Builder $query) => $query->where('status', CardStatus::VerdictProposed)->orderBy('deadline'),
            'upcoming' => fn (Builder $query) => $query
                ->where('status', CardStatus::Published)
                ->where(fn (Builder $deadline) => $deadline->whereNull('deadline')->orWhereDate('deadline', '>=', today()))
                ->orderByRaw('deadline asc nulls last'),
            'closed' => fn (Builder $query) => $query
                ->where('status', CardStatus::Closed)
                ->when($this->tone, fn (Builder $closed) => $this->withVerdictTones($closed, [$this->tone]))
                ->when($this->selectedTones(), fn (Builder $closed, array $tones) => $this->withVerdictTones($closed, $tones))
                ->latest('updated_at'),
        ];

        $keys = match ($this->view) {
            'awaiting' => ['awaiting'],
            'upcoming' => ['upcoming'],
            'review' => ['review'],
            'closed', 'verdict' => ['closed'],
            default => array_keys($scopes),
        };

        // Un état choisi garde sa section ; un verdict choisi ne laisse que les cartes tranchées.
        $states = array_intersect($this->states, array_keys($scopes));
        $keys = array_values(array_filter($keys, fn (string $key) => ($states === [] || in_array($key, $states, true))
            && ($this->selectedTones() === [] || $key === 'closed')));

        return collect($keys)
            ->map(function (string $key) use ($scopes) {
                [$title, $tagline] = self::SECTIONS[$key];
                $query = $scopes[$key]($this->baseQuery());

                return [
                    'key' => $key,
                    'title' => $this->view === 'verdict' ? Appearance::VERDICT_TONES[$this->tone]['label'] : $title,
                    'tagline' => $tagline,
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
    private function baseQuery(bool $withType = true): Builder
    {
        return Card::query()
            ->visible()
            ->with(['publicFigure', 'themes', 'approvedVerdict'])
            ->when($this->figure, fn (Builder $query) => $query->where('public_figure_id', $this->figure->getKey()))
            ->when($this->theme, fn (Builder $query) => $query->whereHas('themes', fn (Builder $theme) => $theme->whereKey($this->theme->getKey())))
            ->when($withType ? CardType::tryFrom($this->type) : null, fn (Builder $query, CardType $type) => $query->where('type', $type))
            ->when($this->figures !== [], fn (Builder $query) => $query->whereHas('publicFigure', fn (Builder $figure) => $figure->whereIn('slug', $this->figures)))
            ->when($this->themes !== [], fn (Builder $query) => $query->whereHas('themes', fn (Builder $theme) => $theme->whereIn('slug', $this->themes)))
            ->when(trim($this->q) !== '', fn (Builder $query) => $query->matching($this->q));
    }

    /**
     * @param  Builder<Card>  $query
     * @param  list<string>  $tones  clés de Appearance::VERDICT_TONES
     */
    private function withVerdictTones(Builder $query, array $tones): void
    {
        $values = collect($tones)
            ->flatMap(fn (string $tone) => VerdictValue::withTone(Appearance::VERDICT_TONES[$tone]['tone']))
            ->all();

        $query->whereHas('approvedVerdict', fn (Builder $verdict) => $verdict->whereIn('value', $values));
    }

    /**
     * @return list<string>
     */
    private function selectedTones(): array
    {
        return array_values(array_intersect($this->verdicts, array_keys(Appearance::VERDICT_TONES)));
    }

    /**
     * Choix des quatre menus de filtre : seules les personnalités et thèmes qui ont une carte publique.
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    private function filterOptions(): array
    {
        if (! $this->filtering) {
            return [];
        }

        return [
            'states' => ['label' => 'États', 'options' => self::STATE_LABELS],
            'verdicts' => ['label' => 'Verdicts', 'options' => array_map(fn (array $tone) => $tone['label'], Appearance::VERDICT_TONES)],
            'figures' => ['label' => 'Personnalités', 'options' => PublicFigure::whereHas('cards', fn (Builder $query) => $query->visible())
                ->orderBy('name')->pluck('name', 'slug')->all()],
            'themes' => ['label' => 'Thèmes', 'options' => Theme::whereHas('cards', fn (Builder $query) => $query->visible())
                ->orderBy('name')->pluck('name', 'slug')->all()],
        ];
    }

    private function title(): string
    {
        return match (true) {
            $this->figure !== null => $this->figure->name,
            $this->theme !== null => '# '.$this->theme->name,
            default => match ($this->view) {
                'awaiting' => 'Verdict attendu',
                'upcoming' => 'Échéances à venir',
                'review' => 'Verdict en relecture',
                'closed' => 'Tranchées',
                'verdict' => Appearance::VERDICT_TONES[$this->tone]['label'],
                'search' => 'Rechercher',
                default => 'Toutes les cartes',
            },
        };
    }

    /**
     * Chapeau sous le titre (accueil : l'accroche de la maquette).
     */
    private function lead(): string
    {
        return match (true) {
            $this->figure !== null => trim(($this->figure->description ?? '').' · Promesses et prédictions citées, datées et sourcées.', ' ·'),
            $this->theme !== null => 'Promesses et prédictions sur ce thème.',
            default => match ($this->view) {
                'awaiting' => 'L’échéance est passée : il reste à confronter la parole aux faits.',
                'upcoming' => 'Les engagements et pronostics dont l’heure approche.',
                'review' => 'Des verdicts ont été proposés : les modérateurs vérifient leurs sources.',
                'closed', 'verdict' => 'Des déclarations confrontées aux faits, sources à l’appui.',
                'search' => 'Dans les citations et leur contexte.',
                default => 'Qui a dit quoi, et pour quand ? Des déclarations sourcées, des échéances suivies et des verdicts documentés.',
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
