{{-- Page du site : barre latérale et colonne éditoriale (maquette éditoriale). Utilisée par layouts/app (Livewire) et les pages de texte. --}}
@props(['title' => null, 'description' => null])
<!doctype html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description])
</head>
<body>
<div class="flex min-h-screen flex-col md:flex-row">
    <x-sidebar />

    <main class="min-w-0 flex-1 px-5 pt-5 pb-14 md:px-6 md:pt-7 lg:px-11">
        <div class="mx-auto flex max-w-[1700px] flex-col">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink pb-[18px] text-[10px] sm:text-xs">
                <span>Un observatoire de la parole publique.</span>
                @php($commit = config('prophet.commit'))
                @if ($commit)
                    <a href="{{ config('prophet.repository').'/commit/'.$commit }}" class="text-muted hover:text-ink" rel="noopener">
                        Version {{ config('prophet.version') }} · {{ substr($commit, 0, 7) }}
                    </a>
                @else
                    <span class="text-muted">Version {{ config('prophet.version') }}</span>
                @endif
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
