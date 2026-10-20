{{-- Une carte de la grille (maquette éditoriale) : aplat de couleur, citation en serif, portrait. --}}
@props(['card', 'position' => 0, 'wide' => false])
@php
    use App\View\Appearance;
    $themes = $card->themes->take(2)->map(fn ($theme) => '# '.$theme->name)->implode(' / ');
@endphp
<a href="{{ route('cards.show', $card) }}" wire:navigate
   @class([
       'group flex flex-col overflow-hidden border border-ink text-ink no-underline transition-shadow duration-100 hover:text-ink hover:shadow-[8px_0_0_#000] motion-reduce:transition-none',
       'min-h-[330px] 2xl:min-h-[365px]' => ! $wide,
       'min-h-[258px] md:col-span-2' => $wide,
       Appearance::surfaceClasses($card, $position),
   ])>
    <span class="flex items-start justify-between gap-3 p-4 text-[10px] leading-normal">
        <span class="flex flex-none items-center gap-1.5"><x-uicon :name="Appearance::typeIcon($card->type)" :size="14" /> {{ $card->type->label() }}</span>
        @if ($themes)
            <span class="max-w-[65%] text-right">{{ $themes }}</span>
        @endif
    </span>
    <blockquote @class([
        'mx-4 mt-2 mb-[22px] max-w-[95%] font-serif leading-[1.04] font-extrabold tracking-[-0.6px]',
        'text-[29px] 2xl:text-[35px]' => ! $wide,
        'text-[30px] md:max-w-[80%] md:text-[36px]' => $wide,
    ])>{{ $card->quote }}</blockquote>
    <span class="mt-auto flex min-h-[110px] items-end gap-[15px] overflow-hidden pl-3">
        <x-portrait :figure="$card->publicFigure" class="translate-y-[3px]" />
        <span class="pb-1.5 text-[21px] leading-none font-bold tracking-[-0.5px]">
            {{ $card->publicFigure->name }}
            @if ($card->publicFigure->description)
                <small class="mt-0.5 block text-[10px] leading-tight font-normal tracking-normal">{{ $card->publicFigure->description }}</small>
            @endif
        </span>
    </span>
    <span class="flex justify-between gap-2 border-t border-ink px-4 py-3 text-[10px]">
        <span>
            @if ($card->deadline)
                Échéance · <time datetime="{{ $card->deadline->toDateString() }}">{{ $card->deadline->translatedFormat('j M Y') }}</time>
            @else
                Sans échéance
            @endif
        </span>
        <span class="font-bold">{{ Appearance::statusLabel($card) }}</span>
    </span>
</a>
