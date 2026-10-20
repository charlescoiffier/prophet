@php
    use App\Enums\CardStatus;
    use App\View\Appearance;
    $verdict = $card->approvedVerdict;
@endphp
<div role="dialog" aria-label="Carte : {{ $card->publicFigure->name }}" class="mx-auto max-w-[1000px] overflow-hidden border border-ink bg-field">
    <div class="flex items-center justify-between gap-3 border-b border-line py-1.5 pr-2 pl-5">
        <nav aria-label="Fil d’Ariane" class="flex min-w-0 items-center gap-2 text-[13px] text-muted">
            <x-glyph name="list" :size="16" />
            <a href="{{ route('home') }}" wire:navigate class="text-muted no-underline">Toutes les cartes</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('figures.show', $card->publicFigure) }}" wire:navigate class="truncate text-muted no-underline">{{ $card->publicFigure->name }}</a>
        </nav>
        <a href="{{ route('home') }}" wire:navigate aria-label="Fermer" class="flex size-11 items-center justify-center text-muted hover:bg-sidebar">
            <x-glyph name="close" :size="20" />
        </a>
    </div>

    @unless ($card->status->isPublic())
        <p class="border-b border-line bg-accent-soft px-5 py-2.5 text-[13px] text-accent-dark">
            Cette carte n’est pas publique : {{ mb_strtolower($card->status->label()) }}.
            @if ($card->rejection_reason)
                Motif : {{ $card->rejection_reason }}
            @endif
        </p>
    @endunless

    <div class="flex flex-wrap">
        <div class="flex min-w-0 flex-[999_1_520px] flex-col gap-[22px] px-5 pt-6 pb-8 sm:px-8">
            <div class="flex items-start gap-3">
                <x-status-ring :card="$card" size="lg" />
                <div class="flex flex-col gap-2">
                    <h1 class="font-serif text-[30px] leading-[1.08] font-extrabold tracking-[-0.6px] sm:text-[38px]">{{ $card->quote }}</h1>
                    @if ($card->context)
                        <p class="leading-relaxed text-subtle">{{ $card->context }}</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-[18px] sm:pl-8">
                @if ($card->testable_statement)
                    <div class="flex flex-col gap-1">
                        <h2 class="text-xs font-semibold text-muted">Ce qui est vérifié</h2>
                        <p class="text-[15px] leading-normal">{{ $card->testable_statement }}</p>
                    </div>
                @endif
                @if ($card->verdict_criterion)
                    <div class="flex flex-col gap-1">
                        <h2 class="text-xs font-semibold text-muted">Critère de verdict</h2>
                        <p class="text-[15px] leading-normal text-ink-soft">{{ $card->verdict_criterion }}</p>
                    </div>
                @endif

                @if ($verdict)
                    <section class="flex flex-col gap-2.5 border p-4 {{ Appearance::verdictPanelClasses($card) }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="flex items-center gap-2 text-[15px] font-bold {{ Appearance::verdictTextClasses($card) }}">
                                <x-glyph name="flag" :size="16" :width="2" />
                                Verdict : {{ mb_strtolower($verdict->value->label()) }}
                            </h2>
                            @if ($verdict->decided_at)
                                <span class="text-xs text-muted">validé le {{ $verdict->decided_at->translatedFormat('j M Y') }}</span>
                            @endif
                        </div>
                        <p class="leading-relaxed">{{ $verdict->justification }}</p>
                        <div class="flex flex-col gap-0.5 text-[13px]">
                            @foreach ($verdict->sources as $source)
                                <x-source-link :source="$source" />
                            @endforeach
                        </div>
                    </section>
                @elseif ($card->status === CardStatus::VerdictProposed)
                    <p class="border border-line bg-sidebar p-4 text-[13px] text-subtle">
                        Un verdict a été proposé : il est en cours de relecture par les modérateurs.
                    </p>
                @endif

                @if ($card->sources->isNotEmpty())
                    <section class="flex flex-col gap-1">
                        <h2 class="mb-1 text-xs font-semibold text-muted">{{ $card->sources->count() > 1 ? 'Sources de la citation' : 'Source de la citation' }}</h2>
                        @foreach ($card->sources as $source)
                            <x-source-link :source="$source" class="text-sm" />
                        @endforeach
                    </section>
                @endif

                @can('propose', [\App\Models\Verdict::class, $card])
                    <livewire:cards.propose-verdict-form :card="$card" />
                @else
                    @guest
                        @if ($card->status === CardStatus::Published)
                            <p class="text-[13px] text-muted">
                                L’échéance est passée ou la promesse a été tenue ? <a href="{{ route('login') }}" wire:navigate>Connectez-vous</a> pour proposer un verdict sourcé.
                            </p>
                        @endif
                    @endguest
                @endcan

                <section class="flex flex-col gap-3 border-t border-line pt-4">
                    <h2 class="text-xs font-semibold text-muted">Historique</h2>
                    @foreach ($card->activityLogs as $log)
                        @php($sentence = $log->publicSentence())
                        <div class="flex items-start gap-2.5" wire:key="log-{{ $log->id }}">
                            <span aria-hidden="true" @class([
                                'flex size-7 flex-none items-center justify-center rounded-full text-[11px] font-bold',
                                'bg-accent-soft text-accent-dark' => $sentence['actor'] !== null,
                                'bg-[#F0F0F0] text-subtle' => $sentence['actor'] === null,
                            ])>{{ Appearance::initials($sentence['actor']) }}</span>
                            <div class="flex flex-col gap-0.5">
                                <span>
                                    @if ($sentence['actor'])
                                        <strong class="font-semibold">{{ $sentence['actor'] }}</strong>
                                    @endif
                                    {{ $sentence['text'] }}
                                </span>
                                @if ($log->comment)
                                    <span class="text-[13px] text-subtle">{{ $log->comment }}</span>
                                @endif
                                <time datetime="{{ $log->created_at->toIso8601String() }}" class="text-xs text-muted">{{ $log->created_at->translatedFormat('j M Y') }}</time>
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>
        </div>

        <aside class="flex flex-[1_1_260px] flex-col gap-1 border-t border-line bg-sidebar p-5 md:border-t-0 md:border-l">
            <div class="flex flex-col gap-1.5 border-b border-line py-2.5">
                <span class="text-xs font-semibold text-muted">Personnalité</span>
                <a href="{{ route('figures.show', $card->publicFigure) }}" wire:navigate class="flex items-center gap-2 font-semibold text-ink no-underline">
                    <x-portrait :figure="$card->publicFigure" size="avatar" />{{ $card->publicFigure->name }}
                </a>
                @if ($affiliation)
                    <span class="text-xs text-muted">{{ trim($affiliation->role.', '.$affiliation->organization, ', ') }}, au moment de la déclaration</span>
                @endif
            </div>
            <div class="flex flex-col gap-1.5 border-b border-line py-2.5">
                <span class="text-xs font-semibold text-muted">Type</span>
                <span>{{ $card->type->label() }}</span>
            </div>
            <div class="flex flex-col gap-1.5 border-b border-line py-2.5">
                <span class="text-xs font-semibold text-muted">Déclarée le</span>
                <time datetime="{{ $card->stated_on->toDateString() }}">{{ $card->stated_on->translatedFormat('j M Y') }}</time>
            </div>
            @if ($card->deadline)
                <div class="flex flex-col gap-1.5 border-b border-line py-2.5">
                    <span class="text-xs font-semibold text-muted">Échéance</span>
                    <span @class(['flex items-center gap-1.5', 'font-semibold text-negative-text' => Appearance::isOverdue($card)])>
                        <x-glyph name="calendar" :size="14" stroke="#6F6F6F" :width="2" />
                        {{ $card->deadline->translatedFormat('j M Y') }}
                        @if ($card->deadline_nature)
                            ({{ mb_strtolower($card->deadline_nature->label()) }})
                        @endif
                    </span>
                </div>
            @endif
            <div class="flex flex-col gap-1.5 border-b border-line py-2.5">
                <span class="text-xs font-semibold text-muted">Verdict</span>
                @if ($verdict)
                    <span class="flex items-center gap-1.5 font-semibold {{ Appearance::verdictTextClasses($card) }}">
                        <x-glyph name="flag" :size="14" :width="2" />
                        {{ $verdict->value->label() }}
                    </span>
                @else
                    <span class="text-muted">{{ Appearance::isOverdue($card) ? 'Attendu' : 'Pas encore' }}</span>
                @endif
            </div>
            @if ($card->themes->isNotEmpty())
                <div class="flex flex-col gap-2 pt-2.5 pb-4">
                    <span class="text-xs font-semibold text-muted">Thèmes</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($card->themes as $theme)
                            <a href="{{ route('themes.show', $theme) }}" wire:navigate class="rounded bg-tag px-2 py-0.5 text-xs text-ink-soft no-underline"># {{ $theme->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($card->status->isPublic())
                <livewire:cards.report-form :card="$card" />
                <div x-data="{ copied: false }" class="mt-2">
                    <button type="button"
                            @click="navigator.share ? navigator.share({ title: document.title, url: '{{ route('cards.show', $card) }}' }) : navigator.clipboard.writeText('{{ route('cards.show', $card) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="min-h-11 w-full cursor-pointer border border-ink bg-field font-semibold hover:border-accent-line">
                        <span x-show="! copied">Partager la carte</span>
                        <span x-show="copied" x-cloak>Lien copié</span>
                    </button>
                </div>
            @endif
        </aside>
    </div>
</div>
