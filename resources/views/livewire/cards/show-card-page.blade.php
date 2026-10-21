{{-- Fiche d'une carte, version éditoriale : une page du site, la carte en grand, puis les faits. --}}
@php
    use App\Enums\CardStatus;
    use App\View\Appearance;
    $verdict = $card->approvedVerdict;
    $themes = $card->themes->map(fn ($theme) => '# '.$theme->name)->implode(' / ');
    $section = 'border-t border-ink py-6';
    $heading = 'mb-4 font-serif text-[27px] leading-none font-extrabold';
    $label = 'text-[11px] font-semibold text-muted';
@endphp
<div class="flex flex-col">
    <div class="flex flex-wrap items-center justify-between gap-3 pt-7 pb-6">
        <nav aria-label="Fil d’Ariane" class="flex min-w-0 items-center gap-2 text-[11px] text-muted">
            <a href="{{ route('home') }}" wire:navigate class="text-muted no-underline hover:text-ink">← Toutes les cartes</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('figures.show', $card->publicFigure) }}" wire:navigate class="truncate text-muted no-underline hover:text-ink">{{ $card->publicFigure->name }}</a>
        </nav>
    </div>

    @unless ($card->status->isPublic())
        <p class="mb-6 border border-ink bg-signal-yellow px-4 py-3 text-[13px]">
            Cette carte n’est pas publique : {{ mb_strtolower($card->status->label()) }}.
            @if ($card->rejection_reason)
                Motif : {{ $card->rejection_reason }}
            @endif
        </p>
    @endunless

    <div class="grid grid-cols-1 gap-[38px] lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="flex min-w-0 flex-col">
            {{-- La carte en grand, comme dans la grille --}}
            <article class="flex flex-col border border-ink {{ Appearance::surfaceClasses($card) }}">
                <div class="flex items-start justify-between gap-3 p-5 text-xs">
                    <span class="flex items-center gap-1.5"><x-uicon :name="Appearance::typeIcon($card->type)" :size="16" /> {{ $card->type->label() }}</span>
                    @if ($themes)
                        <span class="text-right">{{ $themes }}</span>
                    @endif
                </div>
                <h1 class="mx-5 mt-2 mb-8 max-w-[95%] font-serif text-[clamp(34px,4vw,56px)] leading-[1.02] font-extrabold tracking-[-1.2px]">{{ $card->quote }}</h1>
                <div class="mt-auto flex items-end gap-4 overflow-hidden pl-4">
                    <x-portrait :figure="$card->publicFigure" class="translate-y-[3px]" />
                    <div class="pb-2">
                        <a href="{{ route('figures.show', $card->publicFigure) }}" wire:navigate class="text-[26px] leading-none font-bold tracking-[-0.5px] text-ink no-underline hover:underline">{{ $card->publicFigure->name }}</a>
                        @if ($affiliation)
                            <span class="mt-1 block text-xs">{{ trim($affiliation->role.', '.$affiliation->organization, ', ') }}, au moment de la déclaration</span>
                        @elseif ($card->publicFigure->description)
                            <span class="mt-1 block text-xs">{{ $card->publicFigure->description }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap justify-between gap-2 border-t border-ink px-5 py-3 text-xs">
                    <span>Déclarée le {{ $card->stated_on->translatedFormat('j F Y') }}@if ($card->deadline) · Échéance {{ $card->deadline->translatedFormat('j F Y') }}@endif</span>
                    <span class="font-bold">{{ Appearance::statusLabel($card) }}</span>
                </div>
            </article>

            <div class="mt-8 flex flex-col">
                @if ($card->context)
                    <section class="{{ $section }}">
                        <h2 class="{{ $heading }}">Le contexte</h2>
                        <p class="max-w-[760px] text-[15px] leading-relaxed">{{ $card->context }}</p>
                    </section>
                @endif

                @if ($card->testable_statement || $card->verdict_criterion)
                    <section class="{{ $section }}">
                        <h2 class="{{ $heading }}">Ce qui sera vérifié</h2>
                        <div class="grid max-w-[760px] grid-cols-1 gap-6 sm:grid-cols-2">
                            @if ($card->testable_statement)
                                <div>
                                    <h3 class="{{ $label }} mb-1.5">L’énoncé vérifiable</h3>
                                    <p class="text-[15px] leading-relaxed">{{ $card->testable_statement }}</p>
                                </div>
                            @endif
                            @if ($card->verdict_criterion)
                                <div>
                                    <h3 class="{{ $label }} mb-1.5">Le critère de verdict</h3>
                                    <p class="text-[15px] leading-relaxed">{{ $card->verdict_criterion }}</p>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">Le verdict</h2>
                    @if ($verdict)
                        <div class="max-w-[760px] border border-ink p-5 {{ Appearance::verdictPanelClasses($card) }}">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="font-serif text-[30px] leading-none font-extrabold">{{ Appearance::markFor($verdict->value->tone()) }} {{ $verdict->value->label() }}</p>
                                @if ($verdict->decided_at)
                                    <span class="text-xs">validé le {{ $verdict->decided_at->translatedFormat('j F Y') }}</span>
                                @endif
                            </div>
                            <p class="mt-4 text-[15px] leading-relaxed">{{ $verdict->justification }}</p>
                            @if ($verdict->sources->isNotEmpty())
                                <div class="mt-4 flex flex-col gap-1 border-t border-ink/40 pt-3 text-[13px]">
                                    @foreach ($verdict->sources as $source)
                                        <x-source-link :source="$source" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif ($card->status === CardStatus::VerdictProposed)
                        <p class="max-w-[760px] text-[15px] leading-relaxed">Un verdict a été proposé : les modérateurs vérifient ses sources avant de le publier.</p>
                    @else
                        <p class="max-w-[760px] text-[15px] leading-relaxed text-subtle">
                            {{ Appearance::isOverdue($card) ? 'L’échéance est passée : il reste à confronter la parole aux faits.' : 'Pas encore : la carte sera tranchée à son échéance, sources à l’appui.' }}
                        </p>
                        @can('propose', [\App\Models\Verdict::class, $card])
                            <div class="mt-4 max-w-[760px]">
                                <livewire:cards.propose-verdict-form :card="$card" />
                            </div>
                        @else
                            @guest
                                @if ($card->status === CardStatus::Published)
                                    <p class="mt-3 text-[13px] text-muted"><a href="{{ route('login') }}" wire:navigate>Connectez-vous</a> pour proposer un verdict sourcé.</p>
                                @endif
                            @endguest
                        @endcan
                    @endif
                </section>

                @if ($card->sources->isNotEmpty())
                    <section class="{{ $section }}">
                        <h2 class="{{ $heading }}">{{ $card->sources->count() > 1 ? 'Les sources' : 'La source' }}</h2>
                        <div class="flex max-w-[760px] flex-col gap-2">
                            @foreach ($card->sources as $source)
                                <x-source-link :source="$source" class="text-[15px]" />
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">L’historique</h2>
                    <ol class="flex max-w-[760px] flex-col">
                        @foreach ($card->activityLogs as $log)
                            @php($sentence = $log->publicSentence())
                            <li class="flex items-start gap-3 border-b border-line py-3 last:border-b-0" wire:key="log-{{ $log->id }}">
                                <span aria-hidden="true" class="flex size-8 flex-none items-center justify-center border border-ink bg-field font-serif text-[12px] font-extrabold">{{ Appearance::initials($sentence['actor']) }}</span>
                                <div class="flex flex-1 flex-col gap-0.5">
                                    <span class="text-[14px]">
                                        @if ($sentence['actor'])
                                            <strong class="font-semibold">{{ $sentence['actor'] }}</strong>
                                        @endif
                                        {{ $sentence['text'] }}
                                    </span>
                                    @if ($log->comment)
                                        <span class="text-[13px] text-subtle italic">{{ $log->comment }}</span>
                                    @endif
                                </div>
                                <time datetime="{{ $log->created_at->toIso8601String() }}" class="flex-none text-xs text-muted">{{ $log->created_at->translatedFormat('j M Y') }}</time>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        </div>

        <aside class="flex flex-col gap-4 self-start lg:sticky lg:top-6">
            <div class="border border-ink bg-field">
                <h2 class="border-b border-ink px-5 py-4 font-serif text-[24px] leading-none font-extrabold">Les faits</h2>
                <dl class="flex flex-col px-5 text-[13px] [&>div]:flex [&>div]:flex-col [&>div]:gap-1 [&>div]:border-b [&>div]:border-line [&>div]:py-3 [&>div:last-child]:border-b-0">
                    <div>
                        <dt class="{{ $label }}">Statut</dt>
                        <dd class="font-semibold">{{ $card->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">Type</dt>
                        <dd class="flex items-center gap-1.5"><x-uicon :name="Appearance::typeIcon($card->type)" :size="14" /> {{ $card->type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">Déclarée le</dt>
                        <dd><time datetime="{{ $card->stated_on->toDateString() }}">{{ $card->stated_on->translatedFormat('j F Y') }}</time></dd>
                    </div>
                    @if ($card->deadline)
                        <div>
                            <dt class="{{ $label }}">Échéance</dt>
                            <dd @class(['font-semibold text-negative-text' => Appearance::isOverdue($card)])>
                                {{ $card->deadline->translatedFormat('j F Y') }}
                                @if ($card->deadline_nature)
                                    <span class="font-normal text-muted">({{ mb_strtolower($card->deadline_nature->label()) }})</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="{{ $label }}">Verdict</dt>
                        <dd class="font-semibold">{{ $verdict ? Appearance::markFor($verdict->value->tone()).' '.$verdict->value->label() : (Appearance::isOverdue($card) ? 'Attendu' : 'Pas encore') }}</dd>
                    </div>
                    @if ($card->themes->isNotEmpty())
                        <div>
                            <dt class="{{ $label }}">Thèmes</dt>
                            <dd class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($card->themes as $theme)
                                    <a href="{{ route('themes.show', $theme) }}" wire:navigate class="border border-ink px-2 py-0.5 text-xs text-ink no-underline hover:bg-ink hover:text-paper"># {{ $theme->name }}</a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($card->status->isPublic())
                <livewire:cards.report-form :card="$card" />
                <div x-data="{ copied: false }">
                    <button type="button"
                            @click="navigator.share ? navigator.share({ title: document.title, url: '{{ route('cards.show', $card) }}' }) : navigator.clipboard.writeText('{{ route('cards.show', $card) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="min-h-11 w-full cursor-pointer bg-ink font-semibold text-paper hover:bg-black">
                        <span x-show="! copied">Partager la carte</span>
                        <span x-show="copied" x-cloak>Lien copié</span>
                    </button>
                </div>
            @endif
        </aside>
    </div>
</div>
