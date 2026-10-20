{{--
    Portrait d'une personnalité façon photocopie (maquette éditoriale) : la photo libre de droits
    en noir et blanc contrasté, ou à défaut un monogramme typographique.
--}}
@props(['figure', 'size' => 'card'])
@php($classes = $size === 'card' ? 'size-[112px]' : 'size-7')
@if ($figure->photo_url)
    <img src="{{ $figure->photo_url }}" alt="" loading="lazy"
         {{ $attributes->class([$classes, 'flex-none object-cover object-[center_30%] mix-blend-multiply grayscale contrast-[1.7]']) }}>
@else
    <span aria-hidden="true" {{ $attributes->class([
        $classes,
        'flex flex-none items-end justify-start overflow-hidden border border-ink font-serif leading-none font-extrabold text-ink/85',
        'pb-1 pl-1.5 text-[64px] tracking-[-4px]' => $size === 'card',
        'justify-center items-center text-[13px] tracking-[-0.5px]' => $size !== 'card',
    ]) }}>{{ \App\View\Appearance::monogram($figure) }}</span>
@endif
