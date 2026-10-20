{{-- Petite fenêtre centrée pour les pages de compte et de confirmation. --}}
@props(['title'])
<div class="mx-auto flex w-full max-w-[440px] flex-col gap-4 rounded-xl bg-white p-6 shadow-[0_16px_48px_rgba(0,0,0,0.18)] sm:mt-10">
    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 font-bold text-ink no-underline hover:text-ink">
        <span aria-hidden="true" class="flex size-[26px] items-center justify-center rounded-[7px] bg-accent pt-1.5 font-serif text-xl leading-none text-white">“</span>
        {{ config('app.name') }}
    </a>
    <h1 class="text-xl font-bold">{{ $title }}</h1>
    {{ $slot }}
</div>
