{{-- Rond d'état d'une carte : couleur du verdict, orange si l'échéance est dépassée. --}}
@props(['card', 'size' => 'sm'])
<span aria-hidden="true" @class([
    'flex-none rounded-full border-2',
    'size-4' => $size === 'sm',
    'mt-1.5 size-5' => $size === 'lg',
    \App\View\Appearance::ringClasses($card),
])></span>
