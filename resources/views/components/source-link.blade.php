{{-- Lien vers une source, avec sa copie archivée (règle 7). --}}
@props(['source'])
@php
    $label = collect([
        $source->kind->label(),
        $source->outlet,
        $source->published_on?->translatedFormat('j F Y'),
    ])->filter()->implode(', ');
    $label = $label ?: parse_url($source->url, PHP_URL_HOST);
@endphp
<div class="flex flex-col">
    <a href="{{ $source->url }}" target="_blank" rel="noopener nofollow" {{ $attributes->merge(['class' => 'flex min-h-8 items-center gap-2']) }}>
        <x-uicon name="link" :size="14" />
        {{ $label }}@if ($source->timecode), à {{ $source->timecode }}@endif
    </a>
    @if ($source->archive_url)
        <span class="pl-[22px] text-xs text-muted">
            <a href="{{ $source->archive_url }}" target="_blank" rel="noopener nofollow">Copie archivée</a> le {{ $source->archived_at?->translatedFormat('j M Y') }}
        </span>
    @else
        <span class="pl-[22px] text-xs text-muted">Copie archivée en cours</span>
    @endif
</div>
