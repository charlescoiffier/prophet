@props(['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false])
@php
    $fullTitle = $title ? $title.' — '.config('app.name') : config('app.name');
    $description ??= 'Promesses et prédictions de personnalités publiques, citées mot pour mot, datées, sourcées, puis tranchées par un verdict sourcé.';
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($noindex)
    <meta name="robots" content="noindex">
@endif
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
{{-- Aperçus de partage (Open Graph) --}}
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical ?? url()->current() }}">
<meta name="twitter:card" content="summary">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#F7F4EA">
<meta property="og:image" content="{{ url('/icon-512.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
