@php
    $views = [
        ['route' => 'home', 'label' => 'Toutes les cartes', 'icon' => 'list', 'count' => $counts['all'], 'stroke' => null],
        ['route' => 'cards.awaiting', 'label' => 'Verdict attendu', 'icon' => 'clock', 'count' => $counts['awaiting'], 'stroke' => '#C2410C'],
        ['route' => 'cards.upcoming', 'label' => 'Échéances à venir', 'icon' => 'calendar', 'count' => $counts['upcoming'], 'stroke' => null],
        ['route' => 'cards.closed', 'label' => 'Tranchées', 'icon' => 'check', 'count' => $counts['closed'], 'stroke' => null],
    ];
    $item = 'flex min-h-11 items-center gap-2.5 rounded-md px-2 text-ink no-underline hover:bg-[#F1EFEA] hover:text-ink';
    $active = 'bg-accent-soft font-semibold text-accent-dark hover:bg-accent-soft hover:text-accent-dark';
@endphp
<aside x-data="{ open: false }" class="flex flex-col gap-5 border-b border-line bg-sidebar px-3 pt-2 pb-2 md:w-[260px] md:flex-none md:border-r md:border-b-0 md:pt-4 md:pb-6">
    <div class="flex items-center justify-between">
    <a href="{{ route('home') }}" wire:navigate class="flex min-h-11 items-center gap-2.5 px-2 text-base font-bold text-ink no-underline hover:text-ink">
        <span aria-hidden="true" class="flex size-[26px] items-center justify-center rounded-[7px] bg-accent pt-1.5 font-serif text-xl leading-none text-white">“</span>
        {{ config('app.name') }}
    </a>
        <button type="button" @click="open = ! open" :aria-expanded="open" aria-controls="sidebar-menu"
                class="flex min-h-11 cursor-pointer items-center gap-2 rounded-md px-3 text-[13px] font-semibold text-muted md:hidden">
            <x-glyph name="list" />
            Menu
        </button>
    </div>

    <div id="sidebar-menu" :class="open ? 'flex' : 'hidden md:flex'" class="hidden flex-1 flex-col gap-5 pb-4 md:flex md:pb-0">

    <nav aria-label="Vues" class="flex flex-col gap-0.5">
        <a href="{{ route('cards.propose') }}" wire:navigate class="{{ $item }} font-semibold text-accent-dark">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#2552C6" aria-hidden="true"><circle cx="12" cy="12" r="11"/><path d="M12 7v10M7 12h10" stroke="#FFFFFF" fill="none" stroke-width="2.2" stroke-linecap="round"/></svg>
            <span class="flex-1">Proposer une carte</span>
            <kbd class="rounded border border-line-strong px-1.5 py-0.5 font-sans text-xs font-normal text-muted">Q</kbd>
        </a>
        <a href="{{ route('search') }}" wire:navigate @class([$item, $active => request()->routeIs('search')])>
            <x-glyph name="search" :size="20" stroke="#6F6F6F" />
            Rechercher
        </a>
        @foreach ($views as $view)
            @php($isActive = request()->routeIs($view['route']))
            <a href="{{ route($view['route']) }}" wire:navigate @class([$item, $active => $isActive]) @if ($isActive) aria-current="page" @endif>
                <x-glyph :name="$view['icon']" :size="20" :stroke="$isActive ? '#2552C6' : ($view['stroke'] ?? '#6F6F6F')" />
                <span class="flex-1">{{ $view['label'] }}</span>
                <span @class(['text-xs', 'font-medium' => $isActive, 'text-muted' => ! $isActive])>{{ $view['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($figures->isNotEmpty())
        <section class="flex flex-col gap-0.5">
            <h2 class="px-2 pb-1.5 text-xs font-semibold text-muted">Personnalités</h2>
            @foreach ($figures as $figure)
                <a href="{{ route('figures.show', $figure) }}" wire:navigate
                   @class([$item, 'min-h-10', $active => request()->is('personnalites/'.$figure->slug)])>
                    <x-figure-dot :figure="$figure" />
                    {{ $figure->name }}
                </a>
            @endforeach
        </section>
    @endif

    @if ($themes->isNotEmpty())
        <section class="flex flex-col gap-0.5">
            <h2 class="px-2 pb-1.5 text-xs font-semibold text-muted">Thèmes</h2>
            @foreach ($themes as $theme)
                <a href="{{ route('themes.show', $theme) }}" wire:navigate
                   @class([$item, 'min-h-10', $active => request()->is('themes/'.$theme->slug)])>
                    <span aria-hidden="true" class="w-[9px] text-muted">#</span>
                    {{ $theme->name }}
                </a>
            @endforeach
        </section>
    @endif

    <div class="mt-auto flex flex-col gap-0.5">
        @auth
            <a href="{{ route('account.cards') }}" wire:navigate @class([$item, $active => request()->routeIs('account.cards')])>
                <x-glyph name="user" :size="20" stroke="#6F6F6F" />
                <span class="flex-1">{{ auth()->user()->username }}</span>
            </a>
            @if (auth()->user()->isModerator())
                <a href="{{ url('/moderation') }}" class="{{ $item }}">
                    <x-glyph name="shield" :size="20" stroke="#6F6F6F" />
                    Modération
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $item }} w-full cursor-pointer text-left">
                    <x-glyph name="logout" :size="20" stroke="#6F6F6F" />
                    Se déconnecter
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" wire:navigate class="{{ $item }}">
                <x-glyph name="user" :size="20" stroke="#6F6F6F" />
                Se connecter
            </a>
        @endauth
        <div class="flex flex-wrap gap-x-3 px-2 pt-2 text-xs">
            <a href="{{ route('pages.charter') }}" class="text-muted">Charte</a>
            <a href="{{ route('pages.privacy') }}" class="text-muted">Données personnelles</a>
            <a href="{{ url('/docs/api') }}" class="text-muted">API</a>
        </div>
    </div>
    </div>
</aside>
