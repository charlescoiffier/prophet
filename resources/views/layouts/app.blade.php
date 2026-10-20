{{-- Mise en page principale : barre latérale et colonne éditoriale (maquette éditoriale). --}}
<!doctype html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null])
</head>
<body>
<div class="flex min-h-screen flex-col md:flex-row">
    <x-sidebar />

    <main class="min-w-0 flex-1 px-5 pt-5 pb-14 md:px-6 md:pt-7 lg:px-11">
        <div class="mx-auto flex max-w-[1700px] flex-col">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink pb-[18px] text-[10px] sm:text-xs">
                <span>Un observatoire de la parole publique.</span>
                <span class="flex items-center gap-3 text-muted">
                    @php($commit = config('prophet.commit'))
                    <a href="{{ config('prophet.repository').($commit ? '/commit/'.$commit : '/releases') }}" class="text-muted hover:text-ink" rel="noopener">
                        Version {{ config('prophet.version') }}@if ($commit) · {{ substr($commit, 0, 7) }}@endif
                    </a>
                    <a href="{{ config('prophet.repository') }}" class="flex items-center gap-1 text-muted hover:text-ink" rel="noopener">
                        <x-uicon name="github" :size="14" /> GitHub
                    </a>
                </span>
            </div>

            {{ $slot }}

            <footer class="mt-4 flex flex-wrap justify-between gap-x-5 gap-y-2 border-t border-ink pt-4 text-[10px] text-muted">
                <span class="flex flex-wrap gap-3">
                    <a href="{{ route('pages.charter') }}" class="text-muted">Charte du contributeur</a>
                    <a href="{{ route('pages.privacy') }}" class="text-muted">Données personnelles</a>
                    <a href="{{ url('/docs/api') }}" class="text-muted">API</a>
                </span>
                <span>Icônes : <a href="https://www.flaticon.com/uicons" class="text-muted underline" rel="noopener">UIcons par Flaticon</a></span>
            </footer>
        </div>
    </main>
</div>
</body>
</html>
