{{-- Une carte de la grille (docs/maquettes/index.html). --}}
@props(['card'])
@php($overdue = \App\View\Appearance::isOverdue($card))
<a href="{{ route('cards.show', $card) }}" wire:navigate
   class="flex flex-col gap-3 rounded-[10px] border border-[#E8E8E8] bg-white p-4 text-ink no-underline shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition hover:border-accent-line hover:text-ink">
    <span class="flex items-center gap-2 text-xs text-muted">
        <x-status-ring :card="$card" />
        <span class="flex-1">{{ $card->type->label() }}</span>
        @if ($theme = $card->themes->first())
            <span class="whitespace-nowrap"># {{ $theme->name }}</span>
        @endif
    </span>
    <span class="text-[15px] leading-[1.45] font-medium">{{ $card->quote }}</span>
    <span class="flex items-center gap-2 text-[13px]">
        <x-figure-dot :figure="$card->publicFigure" />
        {{ $card->publicFigure->name }}
    </span>
    <span class="flex flex-wrap items-center gap-x-3.5 gap-y-1 border-t border-[#F2F2F2] pt-2.5 text-xs text-muted">
        @if ($card->deadline)
            <span @class(['inline-flex items-center gap-1', 'font-semibold text-negative-text' => $overdue])>
                <x-glyph name="calendar" :size="13" :width="2" />
                <time datetime="{{ $card->deadline->toDateString() }}">{{ $card->deadline->translatedFormat('j M Y') }}</time>
            </span>
        @endif
        @if ($card->approvedVerdict)
            <span class="inline-flex items-center gap-1 font-semibold {{ \App\View\Appearance::verdictTextClasses($card) }}">
                <x-glyph name="flag" :size="13" :width="2" />
                {{ $card->approvedVerdict->value->label() }}
            </span>
        @elseif ($card->status === \App\Enums\CardStatus::VerdictProposed)
            <span class="inline-flex items-center gap-1">Verdict en relecture</span>
        @endif
    </span>
</a>
