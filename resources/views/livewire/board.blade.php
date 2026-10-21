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
        @if ($searching)
            <label class="flex min-h-10 w-full items-center gap-2 border border-ink bg-field px-3 sm:w-auto sm:min-w-[420px] lg:min-w-[520px]">
                <x-uicon name="search" :size="20" />
                <span class="sr-only">Filtrer les cartes par citation ou par personnalité</span>
                <input type="text" enterkeyhint="search" wire:model.live.debounce.300ms="q" x-init="$el.focus()" @keydown.escape="$wire.closeSearch()"
                       placeholder="Filtrer par citation ou par personnalité"
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
                Aucune carte ne correspond à « {{ $q }} ».
            @else
                Aucune carte pour l’instant.
            @endif
        </p>
    @endforelse
</div>
