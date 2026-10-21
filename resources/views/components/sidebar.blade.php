@php
    use App\View\Appearance;
    $states = [
        ['route' => 'home', 'label' => 'Toutes les cartes', 'icon' => 'all-cards', 'count' => $counts['all']],
        ['route' => 'cards.awaiting', 'label' => 'Verdict attendu', 'icon' => 'awaiting', 'count' => $counts['awaiting']],
        ['route' => 'cards.upcoming', 'label' => 'Échéances à venir', 'icon' => 'upcoming', 'count' => $counts['upcoming']],
        ['route' => 'cards.review', 'label' => 'En relecture', 'icon' => 'review', 'count' => $counts['review']],
        ['route' => 'cards.closed', 'label' => 'Tranchées', 'icon' => 'closed', 'count' => $counts['closed']],
    ];
    $item = 'flex min-h-10 items-center gap-2.5 px-2.5 py-2 text-[13px] leading-tight font-semibold text-ink no-underline hover:bg-paper hover:text-ink';
    $active = 'underline decoration-2 underline-offset-[5px]';
    $label = 'mx-2.5 mb-2 text-[11px] font-semibold text-muted';
    $icon = 'flex size-7 flex-none items-center justify-center';
@endphp
<aside x-data="{ open: false }"
       class="flex flex-col border-b border-ink bg-paper-deep px-4 py-4 md:sticky md:top-0 md:h-screen md:w-[272px] md:flex-none md:overflow-y-auto md:border-r md:border-b-0 md:px-[22px] md:py-[30px]">
    <div class="flex items-center justify-between">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 whitespace-nowrap text-ink no-underline hover:text-ink">
            <img src="/logo.svg" alt="" width="38" height="38" class="size-[38px] flex-none">
            <span class="font-serif text-[27px] leading-none font-extrabold tracking-[-1px] text-black">{{ config('app.name') }}.</span>
        </a>
        <button type="button" @click="open = ! open" :aria-expanded="open" aria-controls="sidebar-menu"
                class="flex min-h-11 cursor-pointer items-center gap-2 px-3 text-[13px] font-semibold md:hidden">
            <x-uicon name="menu" :size="16" /> Menu
        </button>
    </div>
    <p class="mt-3 hidden text-[11px] text-muted md:block">Les paroles passent, les promesses restent.</p>

    {{-- Ouvert sur téléphone par le bouton Menu (data-open), toujours visible sur grand écran. --}}
    <div id="sidebar-menu" :data-open="open" class="hidden flex-1 flex-col data-[open=true]:flex md:flex">
        <div class="mt-5 flex flex-col gap-2 md:mt-9">
            <a href="{{ route('cards.propose') }}" wire:navigate class="flex items-center gap-2.5 bg-ink px-2.5 py-3 text-sm font-semibold whitespace-nowrap text-white no-underline hover:bg-black hover:text-white">
                <x-uicon name="propose" :size="18" />
                <span class="flex-1">Proposer une carte</span>
                <kbd class="border border-white/40 px-1.5 font-sans text-[11px] font-normal">Q</kbd>
            </a>
            <a href="{{ route('search') }}" wire:navigate class="flex items-center gap-2.5 px-2.5 py-3 text-sm font-semibold text-ink no-underline hover:bg-paper hover:text-ink">
                <x-uicon name="search" :size="20" />
                Rechercher
            </a>
        </div>

        <nav aria-label="Explorer les cartes" class="flex flex-col">
            <section class="mt-[25px] flex flex-col">
                <h2 class="{{ $label }}">États</h2>
                @foreach ($states as $state)
                    @php($isActive = request()->routeIs($state['route']))
                    <a href="{{ route($state['route']) }}" wire:navigate class="{{ $item }}" @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $icon }}"><x-uicon :name="$state['icon']" :size="18" /></span>
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
                        <span class="{{ $icon }}"><x-uicon :name="$tone['icon']" :size="18" /></span>
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
                            <span class="{{ $icon }}"><x-uicon name="theme" :size="16" /></span>
                            <span @class(['flex-1', $active => $isActive])>{{ $theme->name }}</span>
                        </a>
                    @endforeach
                </section>
            @endif
        </nav>

        <div class="mt-[25px] flex flex-col border-t border-line pt-4 md:mt-auto">
            @auth
                <a href="{{ route('account.cards') }}" wire:navigate @class([$item, $active => request()->routeIs('account.cards')])>
                    <span class="{{ $icon }}"><x-uicon name="account" :size="18" /></span>
                    <span class="flex-1">{{ auth()->user()->username }} · mes propositions</span>
                </a>
                @if (auth()->user()->isModerator())
                    <a href="{{ url('/moderation') }}" class="{{ $item }}">
                        <span class="{{ $icon }}"><x-uicon name="moderation" :size="18" /></span>
                        Modération
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $item }} w-full cursor-pointer text-left">
                        <span class="{{ $icon }}"><x-uicon name="logout" :size="18" /></span>
                        Se déconnecter
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="{{ $item }}">
                    <span class="{{ $icon }}"><x-uicon name="login" :size="18" /></span>
                    Se connecter
                </a>
            @endauth
        </div>
    </div>
</aside>
