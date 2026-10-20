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
            <div class="flex items-center justify-between gap-3 border-b border-ink pb-[18px] text-[10px] sm:text-xs">
                <span>Un observatoire de la parole publique</span>
                @if (config('prophet.test_notice'))
                    <span class="bg-signal-yellow px-2 py-1 text-[10px]">Version de test</span>
                @endif
            </div>

            {{ $slot }}

            <footer class="mt-10 flex flex-wrap justify-between gap-x-5 gap-y-2 border-t border-ink pt-4 text-[10px] text-muted">
                <span>{{ config('app.name') }}. Les mots engagent.</span>
                <span class="flex gap-3">
                    <a href="{{ route('pages.charter') }}" class="text-muted">Charte du contributeur</a>
                    <a href="{{ route('pages.privacy') }}" class="text-muted">Données personnelles</a>
                    <a href="{{ url('/docs/api') }}" class="text-muted">API</a>
                </span>
            </footer>
        </div>
    </main>
</div>
</body>
</html>
