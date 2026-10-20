@php
    use App\View\Appearance;
    $states = [
        ['route' => 'home', 'label' => 'Toutes les cartes', 'mark' => '▦', 'count' => $counts['all']],
        ['route' => 'cards.awaiting', 'label' => 'Verdict attendu', 'mark' => '◷', 'count' => $counts['awaiting']],
        ['route' => 'cards.upcoming', 'label' => 'Échéances à venir', 'mark' => '□', 'count' => $counts['upcoming']],
        ['route' => 'cards.review', 'label' => 'En relecture', 'mark' => '⌕', 'count' => $counts['review']],
        ['route' => 'cards.closed', 'label' => 'Tranchées', 'mark' => '✓', 'count' => $counts['closed']],
    ];
    $item = 'flex min-h-10 items-center gap-2.5 px-2.5 py-2 text-[13px] leading-tight font-semibold text-ink no-underline hover:bg-paper hover:text-ink';
    $active = 'underline decoration-2 underline-offset-[5px]';
    $label = 'mx-2.5 mb-2 text-[11px] font-semibold text-muted';
    $mark = 'flex size-7 flex-none items-center justify-center text-[19px] font-medium';
@endphp
<aside x-data="{ open: false }"
       class="flex flex-col border-b border-ink bg-paper-deep px-4 py-4 md:sticky md:top-0 md:h-screen md:w-[258px] md:flex-none md:overflow-y-auto md:border-r md:border-b-0 md:px-[22px] md:py-[30px]">
    <div class="flex items-center justify-between">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 whitespace-nowrap text-ink no-underline hover:text-ink">
            <span aria-hidden="true" class="relative isolate flex size-[35px] flex-none items-center justify-center pt-[7px] font-[Georgia,serif] text-[45px] leading-none font-extrabold text-black before:absolute before:inset-0 before:-z-10 before:-rotate-[8deg] before:border-[1.5px] before:border-ink before:content-['']">“</span>
            <span class="font-serif text-[27px] leading-none font-extrabold tracking-[-1px] text-black">{{ config('app.name') }}.</span>
        </a>
        <button type="button" @click="open = ! open" :aria-expanded="open" aria-controls="sidebar-menu"
                class="flex min-h-11 cursor-pointer items-center gap-2 px-3 text-[13px] font-semibold md:hidden">
            ☰ Menu
        </button>
    </div>
    <p class="mt-3 hidden text-[11px] text-muted md:block">Les mots restent. Les faits tranchent.</p>

    <div id="sidebar-menu" :class="open ? 'flex' : 'hidden md:flex'" class="hidden flex-1 flex-col md:flex">
        <div class="mt-5 flex flex-col gap-2 md:mt-9">
            <a href="{{ route('cards.propose') }}" wire:navigate class="flex items-center justify-between bg-ink px-2.5 py-3 text-sm font-semibold text-white no-underline hover:bg-black hover:text-white">
                <span>＋ &nbsp; Proposer une carte</span>
                <kbd class="border border-white/40 px-1.5 font-sans text-[11px] font-normal">Q</kbd>
            </a>
            <a href="{{ route('search') }}" wire:navigate @class(['flex items-center px-2.5 py-3 text-sm font-semibold text-ink no-underline hover:bg-paper hover:text-ink', $active => request()->routeIs('search')])>
                ⌕ &nbsp; Rechercher
            </a>
        </div>

        <nav aria-label="Explorer les cartes" class="flex flex-col">
            <section class="mt-[25px] flex flex-col">
                <h2 class="{{ $label }}">États</h2>
                @foreach ($states as $state)
                    @php($isActive = request()->routeIs($state['route']))
                    <a href="{{ route($state['route']) }}" wire:navigate class="{{ $item }}" @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $mark }}" aria-hidden="true">{{ $state['mark'] }}</span>
                        <span @class(['flex-1', $active => $isActive])>{{ $state['label'] }}</span>
                        <span class="text-[11px] font-normal">{{ $state['count'] }}</span>
                    </a>
                @endforeach
            </section>

            <section class="mt-[25px] flex flex-col">
                <h2 class="{{ $label }}">Verdicts</h2>
                @foreach (Appearance::VERDICT_TONES as $slug => $tone)
                    @php($isActive = request()->is('verdicts/'.$slug))
                    <a href="{{ route('cards.verdict', $slug) }}" wire:navigate class="{{ $item }}" @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $mark }}" aria-hidden="true">{{ $tone['mark'] }}</span>
                        <span @class(['flex-1', $active => $isActive])>{{ $tone['label'] }}</span>
                        <span class="text-[11px] font-normal">{{ $toneCounts[$slug] }}</span>
                    </a>
                @endforeach
            </section>

            @if ($figures->isNotEmpty())
                <section class="mt-[25px] flex flex-col">
                    <h2 class="{{ $label }}">Personnalités</h2>
                    @foreach ($figures as $figure)
                        @php($isActive = request()->is('personnalites/'.$figure->slug))
                        <a href="{{ route('figures.show', $figure) }}" wire:navigate class="{{ $item }}" @if ($isActive) aria-current="page" @endif>
                            <x-portrait :figure="$figure" size="avatar" />
                            <span @class(['flex-1', $active => $isActive])>{{ $figure->name }}</span>
                        </a>
                    @endforeach
                </section>
            @endif

            @if ($themes->isNotEmpty())
                <section class="mt-[25px] flex flex-col">
                    <h2 class="{{ $label }}">Thèmes</h2>
                    @foreach ($themes as $theme)
                        @php($isActive = request()->is('themes/'.$theme->slug))
                        <a href="{{ route('themes.show', $theme) }}" wire:navigate class="{{ $item }}" @if ($isActive) aria-current="page" @endif>
                            <span class="{{ $mark }}" aria-hidden="true">#</span>
                            <span @class(['flex-1', $active => $isActive])>{{ $theme->name }}</span>
                        </a>
                    @endforeach
                </section>
            @endif
        </nav>

        <div class="mt-[25px] flex flex-col border-t border-line pt-4 text-[13px] md:mt-auto">
            @auth
                <a href="{{ route('account.cards') }}" wire:navigate @class([$item, $active => request()->routeIs('account.cards')])>
                    {{ auth()->user()->username }} · mes propositions
                </a>
                @if (auth()->user()->isModerator())
                    <a href="{{ url('/moderation') }}" class="{{ $item }}">Modération</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $item }} w-full cursor-pointer text-left">Se déconnecter</button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="{{ $item }}">Se connecter</a>
            @endauth
            <p class="mt-3 px-2.5 text-[11px] leading-relaxed text-muted">
                <a href="{{ route('pages.charter') }}" class="text-muted">Charte</a> ·
                <a href="{{ route('pages.privacy') }}" class="text-muted">Données personnelles</a> ·
                <a href="{{ url('/docs/api') }}" class="text-muted">API</a>
            </p>
        </div>
    </div>
</aside>
