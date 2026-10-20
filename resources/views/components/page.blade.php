{{-- Page Blade simple (hors Livewire) sur fond gris, pour les confirmations et les textes. --}}
@props(['title', 'description' => null, 'noindex' => false])
<!doctype html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description, 'noindex' => $noindex])
</head>
<body class="bg-backdrop">
<div class="min-h-screen px-3 py-6 sm:px-5 sm:py-12">
    {{ $slot }}
</div>
</body>
</html>
