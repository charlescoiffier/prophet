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
        @if ($view !== 'search')
            <a href="{{ route('search') }}" wire:navigate class="border border-line-strong px-3 py-[7px] text-xs text-ink no-underline hover:border-ink hover:text-ink">⌕ &nbsp; Rechercher</a>
        @endif
    </div>

    @if ($view === 'search')
        <label class="mt-6 flex min-h-12 items-center gap-2 border border-ink bg-field px-3">
            <span aria-hidden="true">⌕</span>
            <span class="sr-only">Rechercher une citation</span>
            <input type="search" wire:model.live.debounce.400ms="q" autofocus placeholder="Rechercher dans les citations et leur contexte"
                   class="min-w-0 flex-1 bg-transparent text-sm outline-none">
        </label>
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
            <div class="mt-[30px] mb-[15px] flex items-center justify-between gap-3">
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
                        <div class="hidden flex-col justify-between gap-6 border border-ink p-6 lg:flex">
                            <span aria-hidden="true" class="relative block size-12 rounded-full bg-signal-red after:absolute after:left-6 after:h-12 after:w-6 after:rounded-r-full after:bg-signal-yellow after:content-['']"></span>
                            <div>
                                <h3 class="mb-2 font-serif text-[27px] leading-[1.05] font-extrabold">Une citation.<br>Des sources. Des faits.</h3>
                                <p class="text-xs leading-relaxed">Chaque verdict confronte une déclaration mot pour mot à des éléments vérifiables. La nuance fait partie de la réponse.</p>
                            </div>
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
            @if ($view === 'search' && trim($q) === '')
                Tapez un mot de la citation ou de son contexte.
            @elseif ($view === 'search')
                Aucune carte ne correspond à « {{ $q }} ».
            @else
                Aucune carte pour l’instant.
            @endif
        </p>
    @endforelse
</div>
