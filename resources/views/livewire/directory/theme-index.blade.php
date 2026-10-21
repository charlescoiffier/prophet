<div class="flex flex-col">
    <header class="flex flex-col gap-6 pt-7 pb-6 lg:flex-row lg:items-end lg:justify-between lg:pt-[35px] lg:pb-[30px]">
        <h1 class="max-w-[760px] font-serif text-[clamp(40px,4.4vw,72px)] leading-[0.97] font-extrabold tracking-[-2.5px] text-balance">Tous les thèmes</h1>
        <p class="max-w-[450px] text-[13px] leading-relaxed lg:max-w-[255px]">Ceux qui comptent au moins une carte publique, par ordre alphabétique.</p>
    </header>

    <div class="grid grid-cols-1 gap-[18px] border-t border-ink pt-6 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($themes as $theme)
            <a href="{{ route('themes.show', $theme) }}" wire:navigate wire:key="theme-{{ $theme->id }}"
               class="flex items-center justify-between gap-4 border border-ink bg-field px-5 py-4 text-ink no-underline hover:bg-paper-deep hover:text-ink">
                <span class="font-serif text-[24px] leading-none font-extrabold"># {{ $theme->name }}</span>
                <span class="text-xs text-muted">{{ trans_choice(':count carte|:count cartes', $theme->cards_count) }}</span>
            </a>
        @endforeach
    </div>
</div>
