<div class="flex flex-col gap-8">
    <header class="flex flex-col gap-4 pt-7 lg:pt-[35px]">
        <h1 class="font-serif text-[clamp(40px,4.4vw,64px)] leading-[0.97] font-extrabold tracking-[-2px]">Mes propositions</h1>
        <p class="text-[13px] text-muted">Vos cartes et vos verdicts, avec leur statut. <a href="{{ route('account.settings') }}" wire:navigate>Mon compte</a></p>
    </header>

    @if (session('status'))
        <p role="status" class="bg-accent-soft px-3 py-2.5 text-[13px] text-accent-dark">{{ session('status') }}</p>
    @endif

    @unless (auth()->user()->hasVerifiedEmail())
        <div class="flex flex-wrap items-center justify-between gap-2 border border-[#F0DFB4] bg-[#FFF9EC] px-4 py-3 text-[13px] text-partial-text">
            <span>Confirmez votre adresse e-mail pour proposer des cartes et des verdicts : un lien vous a été envoyé.</span>
            <button type="button" wire:click="resendVerification" class="min-h-10 cursor-pointer px-3 font-semibold hover:bg-white">Renvoyer le lien</button>
        </div>
    @endunless

    <section class="flex flex-col">
        <div class="flex items-center gap-2 border-b border-line pb-2">
            <h2 class="text-sm font-bold">Cartes</h2>
            <span class="text-xs text-muted">{{ $cards->count() }}</span>
        </div>
        @forelse ($cards as $card)
            <a href="{{ route('cards.show', $card) }}" wire:navigate wire:key="card-{{ $card->id }}"
               class="flex flex-wrap items-center gap-3 border-b border-line py-3 text-ink no-underline hover:bg-sidebar hover:text-ink">
                <x-status-ring :card="$card" />
                <span class="min-w-0 flex-1">
                    <span class="font-medium">{{ $card->quote }}</span>
                    <span class="block text-xs text-muted">{{ $card->publicFigure?->name ?? $card->requested_figure.' (à ajouter)' }} · {{ $card->type->label() }}</span>
                </span>
                <span class="rounded bg-tag px-2 py-0.5 text-xs text-ink-soft">{{ $card->status->label() }}</span>
            </a>
        @empty
            <p class="py-6 text-muted">Aucune carte pour l’instant. <a href="{{ route('cards.propose') }}" wire:navigate>Proposer une carte</a></p>
        @endforelse
    </section>

    @if ($verdicts->isNotEmpty())
        <section class="flex flex-col">
            <div class="flex items-center gap-2 border-b border-line pb-2">
                <h2 class="text-sm font-bold">Verdicts proposés</h2>
                <span class="text-xs text-muted">{{ $verdicts->count() }}</span>
            </div>
            @foreach ($verdicts as $verdict)
                <a href="{{ route('cards.show', $verdict->card) }}" wire:navigate wire:key="verdict-{{ $verdict->id }}"
                   class="flex flex-wrap items-center gap-3 border-b border-line py-3 text-ink no-underline hover:bg-sidebar hover:text-ink">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium">{{ $verdict->value->label() }}</span>
                        <span class="block text-xs text-muted">{{ $verdict->card->quote }}</span>
                    </span>
                    <span class="rounded bg-tag px-2 py-0.5 text-xs text-ink-soft">{{ $verdict->status->label() }}</span>
                </a>
            @endforeach
        </section>
    @endif
</div>
