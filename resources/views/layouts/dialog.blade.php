{{-- Page en forme de fenêtre sur fond gris (docs/maquettes/Carte.html et Proposer.html). --}}
<!doctype html>
<html lang="fr">
<head>
    @include('partials.head', [
        'title' => $title ?? null,
        'description' => $description ?? null,
        'noindex' => $noindex ?? false,
    ])
</head>
<body class="bg-backdrop">
<div class="min-h-screen px-3 py-6 sm:px-5 sm:py-12">
    {{ $slot }}
</div>
</body>
</html>
