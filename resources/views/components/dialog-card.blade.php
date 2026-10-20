{{-- Petite fenêtre centrée pour les pages de compte et de confirmation. --}}
@props(['title'])
<div class="mx-auto flex w-full max-w-[440px] flex-col gap-4 bg-field p-6 shadow-[0_16px_48px_rgba(0,0,0,0.18)] sm:mt-10">
    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 font-bold text-ink no-underline hover:text-ink">
        <span aria-hidden="true" class="flex size-[26px] items-center justify-center border-[1.5px] border-ink pt-1.5 font-[Georgia,serif] text-xl leading-none text-ink">“</span>
        {{ config('app.name') }}
    </a>
    <h1 class="font-serif text-[30px] leading-[1.05] font-extrabold tracking-[-0.5px]">{{ $title }}</h1>
    {{ $slot }}
</div>
