{{-- Page de texte du site (charte, données personnelles) : même charte que la page de proposition. --}}
@props(['title', 'lead', 'description' => null])
<x-site-page :title="$title" :description="$description ?? $lead">
    <header class="pt-7 pb-6 lg:pt-[35px] lg:pb-[30px]">
        <a href="{{ route('home') }}" class="text-[11px] text-muted no-underline hover:text-ink">← Toutes les cartes</a>
        <h1 class="mt-6 font-serif text-[clamp(40px,4.4vw,60px)] leading-[0.97] font-extrabold tracking-[-2px]">{{ $title }}</h1>
        <p class="mt-4 max-w-[580px] text-[13px] leading-relaxed text-subtle">{{ $lead }}</p>
    </header>

    <div class="grid grid-cols-1 gap-[38px] lg:grid-cols-[minmax(0,1fr)_280px]">
        <article class="flex max-w-[760px] flex-col text-[14px] leading-relaxed
                        [&_h2]:mb-4 [&_h2]:font-serif [&_h2]:text-[27px] [&_h2]:leading-none [&_h2]:font-extrabold
                        [&_li]:pl-1 [&_p+p]:mt-3 [&_section]:border-t [&_section]:border-ink [&_section]:py-6
                        [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-5">
            {{ $slot }}
        </article>

        @isset($aside)
            <aside class="self-start border border-ink bg-signal-yellow p-6 text-xs leading-relaxed lg:sticky lg:top-6
                          [&_h2]:mb-4 [&_h2]:font-serif [&_h2]:text-[30px] [&_h2]:leading-[1.05] [&_h2]:font-extrabold [&_p+p]:mt-3">
                {{ $aside }}
            </aside>
        @endisset
    </div>
</x-site-page>
