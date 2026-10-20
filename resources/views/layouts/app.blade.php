{{-- Mise en page principale : barre latérale de vues et grille de cartes (docs/maquettes/index.html). --}}
<!doctype html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null])
</head>
<body>
<div class="flex min-h-screen flex-col md:flex-row">
    <x-sidebar />

    <main class="min-w-0 flex-1 px-4 pt-7 pb-14 sm:px-8">
        <div class="mx-auto flex max-w-[1040px] flex-col gap-8">
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
