<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-0.5">
            <h1 class="flex items-center gap-2 text-[22px] font-bold">
                @if ($figure)
                    <x-figure-dot :figure="$figure" />
                @endif
                {{ $heading }}
            </h1>
            @if ($figure)
                <span class="text-xs text-muted">
                    {{ $figure->description }}
                    @if ($currentAffiliation)
                        · {{ trim($currentAffiliation->role.', '.$currentAffiliation->organization, ', ') }}
                    @endif
                </span>
            @endif
        </div>

        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = ! open" :aria-expanded="open"
                    class="flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md px-2.5 text-[13px] text-muted hover:bg-sidebar">
                <x-glyph name="filter" />
                Filtrer
                @if ($type !== '')
                    <span class="rounded bg-accent-soft px-1.5 text-xs font-semibold text-accent-dark">{{ \App\Enums\CardType::from($type)->label() }}</span>
                @endif
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 z-10 mt-1 flex w-48 flex-col rounded-lg border border-line bg-white p-1 shadow-lg">
                @foreach (['' => 'Promesses et prédictions', 'promise' => 'Promesses', 'prediction' => 'Prédictions'] as $value => $label)
                    <button type="button" wire:click="$set('type', '{{ $value }}')" @click="open = false"
                            @class(['min-h-10 cursor-pointer rounded-md px-3 text-left text-[13px] hover:bg-sidebar', 'font-semibold text-accent-dark' => $type === $value])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @if ($view === 'search')
        <label class="flex min-h-11 items-center gap-2 rounded-md border border-line-strong px-3 focus-within:border-accent">
            <x-glyph name="search" stroke="#6F6F6F" />
            <span class="sr-only">Rechercher une citation</span>
            <input type="search" wire:model.live.debounce.400ms="q" autofocus placeholder="Rechercher dans les citations et leur contexte"
                   class="min-w-0 flex-1 border-0 text-sm outline-none">
        </label>
    @endif

    @if ($record)
        <section aria-label="Bilan" class="flex flex-wrap gap-x-6 gap-y-2 rounded-lg border border-line bg-sidebar px-4 py-3 text-[13px]">
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
        <section class="flex flex-col" wire:key="section-{{ $section['key'] }}">
            <div class="flex items-center gap-2 border-b border-line pb-2">
                <x-glyph name="chevron" :size="16" stroke="#6F6F6F" :width="2" />
                <h2 class="text-sm font-bold">{{ $section['title'] }}</h2>
                <span class="text-xs text-muted">{{ $section['total'] }}</span>
            </div>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-3 pt-3.5">
                @foreach ($section['cards'] as $card)
                    <x-card-tile :card="$card" wire:key="card-{{ $card->id }}" />
                @endforeach
            </div>
            @if ($section['total'] > $section['cards']->count())
                <button type="button" wire:click="showMore" class="mt-3 min-h-11 cursor-pointer self-start rounded-md px-2 text-[13px] font-semibold text-accent hover:bg-accent-soft">
                    Afficher plus ({{ $section['total'] - $section['cards']->count() }})
                </button>
            @endif
        </section>
    @empty
        <p class="rounded-lg border border-dashed border-line-strong px-4 py-10 text-center text-muted">
            @if ($view === 'search' && trim($q) === '')
                Tapez un mot de la citation ou de son contexte.
            @elseif ($view === 'search')
                Aucune carte ne correspond à « {{ $q }} ».
            @else
                Aucune carte pour l’instant.
            @endif
        </p>
    @endforelse

    <a href="{{ route('cards.propose') }}" wire:navigate class="flex min-h-11 items-center gap-2.5 px-1 text-muted no-underline hover:text-ink">
        <x-glyph name="plus" stroke="#2552C6" :width="2" />
        Proposer une carte
        <kbd class="ml-auto rounded border border-line-strong px-1.5 py-0.5 font-sans text-xs">Q</kbd>
    </a>
</div>
