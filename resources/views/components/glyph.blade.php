{{-- Icônes des maquettes (traits, 24×24). --}}
@props(['name', 'size' => 18, 'stroke' => 'currentColor', 'width' => 1.8])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" aria-hidden="true"
     stroke-width="{{ $width }}" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    @switch($name)
        @case('list') <path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/> @break
        @case('search') <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('calendar') <rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/> @break
        @case('check') <circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 4.5-6 8-6s6.5 2 8 6"/> @break
        @case('chevron') <path d="M6 9l6 6 6-6"/> @break
        @case('flag') <path d="M5 21V4h12l-2 4 2 4H5"/> @break
        @case('link') <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/> @break
        @case('close') <path d="M6 6l12 12M18 6L6 18"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('tag') <path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.2"/> @break
        @case('filter') <path d="M4 6h16M7 12h10M10 18h4"/> @break
        @case('shield') <path d="M12 3l8 3v6c0 4.5-3.5 8-8 9-4.5-1-8-4.5-8-9V6z"/> @break
        @case('logout') <path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/> @break
    @endswitch
</svg>
