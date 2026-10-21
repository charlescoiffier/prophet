<div class="flex flex-col">
    <header class="flex flex-col gap-6 pt-7 pb-6 lg:flex-row lg:items-end lg:justify-between lg:pt-[35px] lg:pb-[30px]">
        <h1 class="max-w-[760px] font-serif text-[clamp(40px,4.4vw,72px)] leading-[0.97] font-extrabold tracking-[-2.5px] text-balance">Toutes les personnalités</h1>
        <p class="max-w-[450px] text-[13px] leading-relaxed lg:max-w-[255px]">Celles dont au moins une parole est au registre, par ordre alphabétique.</p>
    </header>

    <div class="grid grid-cols-1 gap-[18px] border-t border-ink pt-6 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($figures as $figure)
            @php($affiliation = $figure->affiliationOn(today()))
            <a href="{{ route('figures.show', $figure) }}" wire:navigate wire:key="figure-{{ $figure->id }}"
               class="flex items-end gap-4 overflow-hidden border border-ink bg-field pl-4 text-ink no-underline hover:bg-paper-deep hover:text-ink">
                <x-portrait :figure="$figure" class="translate-y-[3px]" />
                <span class="flex min-w-0 flex-1 flex-col gap-1 py-4 pr-4">
                    @if ($figure->category)
                        <span class="text-[11px] font-semibold text-muted">{{ $figure->category->name }}</span>
                    @endif
                    <span class="text-[20px] leading-tight font-bold tracking-[-0.4px]">{{ $figure->name }}</span>
                    @if ($affiliation)
                        <span class="text-xs">{{ trim($affiliation->role.', '.$affiliation->organization, ', ') }}</span>
                    @endif
                    <span class="mt-1 text-xs text-muted">{{ trans_choice(':count carte|:count cartes', $figure->cards_count) }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
