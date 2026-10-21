@php($isHome = $view === 'all' && ! $figure && ! $theme)
<div class="flex flex-col">
    <header class="flex flex-col gap-6 pt-7 pb-6 lg:flex-row lg:items-end lg:justify-between lg:pt-[35px] lg:pb-[30px]">
        <div class="flex items-end gap-4">
            @if ($figure)
                <x-portrait :figure="$figure" class="hidden sm:flex" />
            @endif
            <h1 class="max-w-[760px] font-serif text-[clamp(40px,4.4vw,72px)] leading-[0.97] font-extrabold tracking-[-2.5px] text-balance">
                {{ $isHome ? 'Les paroles passent, les promesses restent.' : $heading }}
            </h1>
        </div>
        <p class="max-w-[450px] text-[13px] leading-relaxed lg:max-w-[255px]">
            {{ $lead }}
            @if ($currentAffiliation)
                <br><span class="text-muted">{{ trim($currentAffiliation->role.', '.$currentAffiliation->organization, ', ') }}</span>
            @endif
        </p>
    </header>

    <div class="flex flex-wrap items-center justify-between gap-5 border-y border-ink py-[13px]">
        <div class="flex flex-wrap items-center gap-x-[23px] gap-y-2 text-xs font-semibold" role="tablist" aria-label="Type de carte">
            @foreach (['' => 'Toutes les cartes', 'promise' => 'Promesses', 'prediction' => 'Prédictions'] as $value => $label)
                <button type="button" role="tab" wire:click="$set('type', '{{ $value }}')" aria-selected="{{ $type === $value ? 'true' : 'false' }}"
                        @class(['cursor-pointer pb-1', 'border-b-2 border-ink' => $type === $value, 'border-b-2 border-transparent' => $type !== $value])>
                    {{ $label }} <sup>{{ $typeCounts[$value] }}</sup>
                </button>
            @endforeach
        </div>
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            <button type="button" wire:click="$toggle('filtering')" aria-expanded="{{ $filtering ? 'true' : 'false' }}" aria-controls="board-filters"
                    @class(['flex min-h-10 cursor-pointer items-center gap-2 border px-3 text-xs font-semibold', 'border-ink bg-ink text-paper' => $filtering, 'border-line-strong hover:border-ink' => ! $filtering])>
                <x-uicon name="filter" :size="18" /> Filtrer
                @if ($this->activeFilterCount() > 0)
                    <span @class(['inline-grid min-w-5 place-items-center rounded-full px-1 text-[11px]', 'bg-paper text-ink' => $filtering, 'bg-ink text-paper' => ! $filtering])>{{ $this->activeFilterCount() }}</span>
                @endif
            </button>
            @if ($searching)
                <label class="flex min-h-10 w-full items-center gap-2 border border-ink bg-field px-3 sm:w-auto sm:min-w-[420px] lg:min-w-[520px]">
                    <x-uicon name="search" :size="20" />
                    <span class="sr-only">Chercher dans les citations</span>
                    <input type="text" enterkeyhint="search" wire:model.live.debounce.300ms="q" x-init="$el.focus()" @keydown.escape="$wire.closeSearch()"
                           placeholder="Chercher dans les citations"
                           class="min-w-0 flex-1 bg-transparent text-sm outline-none">
                    <button type="button" wire:click="closeSearch" class="cursor-pointer px-1 text-lg leading-none text-muted hover:text-ink" aria-label="Fermer la recherche">×</button>
                </label>
            @else
                <button type="button" wire:click="$set('searching', true)"
                        class="flex min-h-10 cursor-pointer items-center gap-2 border border-line-strong px-3 text-xs font-semibold hover:border-ink">
                    <x-uicon name="search" :size="20" /> Rechercher
                </button>
            @endif
        </div>
    </div>

    @if ($filtering)
        <div id="board-filters" class="flex flex-wrap items-center gap-2 border-b border-ink py-[13px]">
            @foreach ($filterOptions as $filter => $menu)
                @if ($menu['fixed'])
                    <button type="button" disabled title="{{ $menu['fixed'] }}" wire:key="filter-{{ $filter }}"
                            class="flex min-h-10 cursor-not-allowed items-center gap-2 border border-line bg-transparent px-3 text-xs font-semibold text-muted opacity-60">
                        {{ $menu['label'] }} <span aria-hidden="true" class="text-[10px]">▾</span>
                        <span class="sr-only">: {{ $menu['fixed'] }}</span>
                    </button>
                    @continue
                @endif
                @php($selected = count($this->{$filter}))
                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative" wire:key="filter-{{ $filter }}">
                    <button type="button" @click="open = ! open" :aria-expanded="open"
                            @class(['flex min-h-10 cursor-pointer items-center gap-2 border px-3 text-xs font-semibold', 'border-ink bg-signal-yellow' => $selected > 0, 'border-line-strong bg-field hover:border-ink' => $selected === 0])>
                        {{ $menu['label'] }}
                        @if ($selected > 0)
                            <span class="font-normal">({{ $selected }})</span>
                        @endif
                        <span aria-hidden="true" class="text-[10px]">▾</span>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute left-0 z-20 mt-1 flex max-h-[320px] w-[260px] flex-col overflow-y-auto border border-ink bg-field py-1 shadow-[4px_4px_0_0_var(--color-ink)]">
                        @forelse ($menu['options'] as $value => $optionLabel)
                            <label class="flex min-h-10 cursor-pointer items-center gap-2.5 px-3 text-[13px] hover:bg-paper" wire:key="filter-{{ $filter }}-{{ $value }}">
                                <input type="checkbox" wire:model.live="{{ $filter }}" value="{{ $value }}" class="size-4 accent-ink">
                                <span class="flex-1">{{ $optionLabel }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-2 text-[13px] text-muted">Aucun choix pour l’instant.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
            @if ($this->activeFilterCount() > 0)
                <button type="button" wire:click="clearFilters" class="min-h-10 cursor-pointer px-2 text-xs text-muted underline hover:text-ink">Effacer les filtres</button>
            @endif
        </div>
    @endif

    @if ($record)
        <section aria-label="Bilan" class="mt-6 flex flex-wrap gap-x-8 gap-y-2 border border-ink bg-field px-4 py-3 text-[13px]">
            @foreach ($record as $cardType => $values)
                @php($nonZero = array_filter($values))
                <span>
                    <span class="font-semibold">{{ \App\Enums\CardType::from($cardType)->label() }}s tranchées :</span>
                    @if ($nonZero === [])
                        <span class="text-muted">aucune</span>
                    @else
                        {{ collect($nonZero)->map(fn ($count, $value) => $count.' '.mb_strtolower(\App\Enums\VerdictValue::from($value)->label()))->implode(', ') }}
                    @endif
                </span>
            @endforeach
        </section>
    @endif

    @forelse ($sections as $section)
        @php($featured = $isHome && $loop->first && $section['cards']->isNotEmpty())
        <section class="flex flex-col" wire:key="section-{{ $section['key'] }}">
            <div class="mt-[30px] mb-4 flex items-center justify-between gap-3 border-b border-ink pb-3">
                <h2 class="flex items-center text-[19px] font-bold tracking-[-0.5px]">
                    {{ $section['title'] }}
                    <span class="ml-2 inline-grid size-[22px] place-items-center rounded-full border border-line-strong text-[11px] font-normal tracking-normal">{{ $section['total'] }}</span>
                </h2>
                <small class="hidden text-[11px] text-muted sm:block">{{ $section['tagline'] }}</small>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($section['cards'] as $card)
                    <x-card-tile :card="$card" :position="$loop->index" :wide="$featured && $loop->first" wire:key="card-{{ $card->id }}" />
                    @if ($featured && $loop->first)
                        <div class="hidden items-end border border-ink p-6 lg:flex">
                            <p class="font-serif text-[22px] leading-snug italic">Chaque verdict confronte une déclaration mot pour mot à des éléments vérifiables. La nuance fait partie de la réponse.</p>
                        </div>
                    @endif
                @endforeach
            </div>
            @if ($section['total'] > $section['cards']->count())
                <button type="button" wire:click="showMore" class="mt-4 min-h-11 cursor-pointer self-start border border-ink px-4 text-[13px] font-semibold hover:bg-ink hover:text-paper">
                    Afficher plus ({{ $section['total'] - $section['cards']->count() }})
                </button>
            @endif
        </section>
    @empty
        <p class="mt-8 border border-dashed border-line-strong px-4 py-10 text-center text-muted">
            @if (trim($q) !== '')
                Aucune carte ne correspond à « {{ $q }} »{{ $this->activeFilterCount() > 0 ? ' avec ces filtres' : '' }}.
            @elseif ($this->activeFilterCount() > 0)
                Aucune carte ne correspond à ces filtres. <button type="button" wire:click="clearFilters" class="cursor-pointer underline">Effacer les filtres</button>
            @else
                Aucune carte pour l’instant.
            @endif
        </p>
    @endforelse
</div>
