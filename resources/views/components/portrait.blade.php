{{--
    Portrait d'une personnalité façon photocopie (maquette éditoriale) : la photo libre de droits
    en noir et blanc contrasté, ou à défaut un monogramme typographique.
--}}
@props(['figure', 'size' => 'card'])
@php($isCard = $size === 'card')
@if ($figure->photo_url)
    <img src="{{ $figure->photo_url }}" alt="" loading="lazy"
         {{ $attributes->class([
             'flex-none object-cover object-[center_30%] mix-blend-multiply grayscale contrast-[1.7]',
             'size-[112px]' => $isCard,
             'size-7' => ! $isCard,
         ]) }}>
@else
    <span aria-hidden="true" {{ $attributes->class([
        'flex flex-none overflow-hidden border border-ink font-serif leading-none font-extrabold text-ink/85',
        'size-[112px] items-end justify-start pb-1.5 pl-1.5 text-[50px] tracking-[-3px]' => $isCard,
        'size-7 items-center justify-center text-[12px] tracking-[-0.5px]' => ! $isCard,
    ]) }}>{{ \App\View\Appearance::monogram($figure) }}</span>
@endif
