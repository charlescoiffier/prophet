{{--
    Portrait d'une personnalité façon photocopie (maquette éditoriale) : la photo libre de droits
    en noir et blanc contrasté, ou à défaut un monogramme typographique. Si la photo ne se charge pas
    (lien cassé, fichier disparu), le monogramme prend le relais.
--}}
@props(['figure', 'size' => 'card'])
@php
    $isCard = $size === 'card';
    $photo = $figure->photoUrl();
    $sizeClass = $isCard ? 'size-[112px]' : 'size-7';
@endphp
<span x-data="{ failed: {{ $photo ? 'false' : 'true' }} }" {{ $attributes->class(['flex flex-none', $sizeClass]) }}>
    @if ($photo)
        <img src="{{ $photo }}" alt="" loading="lazy" x-show="! failed" x-on:error="failed = true"
             class="{{ $sizeClass }} flex-none object-cover object-[center_30%] mix-blend-multiply grayscale contrast-[1.7]">
    @endif
    <span aria-hidden="true" @if ($photo) x-show="failed" x-cloak @endif @class([
        'flex flex-none overflow-hidden border border-ink font-serif leading-none font-extrabold text-ink/85',
        'size-[112px] items-end justify-start pb-1.5 pl-1.5 text-[50px] tracking-[-3px]' => $isCard,
        'size-7 items-center justify-center text-[12px] tracking-[-0.5px]' => ! $isCard,
    ])>{{ \App\View\Appearance::monogram($figure) }}</span>
</span>
