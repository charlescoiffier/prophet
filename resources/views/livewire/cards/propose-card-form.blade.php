@php
    $chip = 'inline-flex min-h-11 items-center gap-1.5 rounded-md border px-3 text-[13px]';
    $chipOn = 'border-accent-line bg-accent-soft font-semibold text-accent-dark';
    $chipOff = 'border-line-strong bg-white text-subtle';
    $field = 'flex min-h-11 items-center gap-2 rounded-md border border-line-strong px-3 focus-within:border-accent';
@endphp
<div class="flex flex-col items-center gap-4 pt-4 sm:pt-6">
    @if ($sent)
        <div role="status" class="flex w-full max-w-[680px] flex-col gap-3 rounded-xl bg-white p-6 shadow-[0_16px_48px_rgba(0,0,0,0.18)]">
            <h1 class="text-lg font-bold">
                {{ $sent->author_id ? 'Merci, votre carte part en relecture.' : 'Merci ! Vérifiez votre boîte de réception.' }}
            </h1>
            <blockquote class="border-l-[3px] border-accent pl-3 text-subtle">{{ $sent->quote }}</blockquote>
            @if ($sent->author_id)
                <p>Un modérateur va vérifier la citation et la source. Vous serez prévenu par e-mail.</p>
            @else
                <p>Nous vous avons envoyé un lien de confirmation. Votre proposition partira en relecture dès que vous aurez cliqué dessus.</p>
            @endif
            <div class="flex gap-2">
                <a href="{{ route('home') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-md bg-[#F2F2F2] px-4 font-semibold text-ink no-underline">Retour aux cartes</a>
                <a href="{{ route('cards.propose') }}" class="inline-flex min-h-11 items-center rounded-md bg-accent px-4 font-semibold text-white no-underline hover:text-white">Proposer une autre carte</a>
            </div>
        </div>
    @else
        <form wire:submit="submit" role="dialog" aria-label="Proposer une carte"
              class="flex w-full max-w-[680px] flex-col rounded-xl bg-white shadow-[0_16px_48px_rgba(0,0,0,0.18)]">
            <div class="flex flex-col gap-1.5 px-5 pt-[18px] pb-3">
                <label class="block">
                    <span class="sr-only">Citation exacte</span>
                    <textarea wire:model="quote" rows="2" autofocus placeholder="Citation exacte, mot pour mot"
                              class="w-full resize-none border-0 p-0 text-[19px] leading-snug font-semibold outline-none placeholder:text-[#A3A3A3]"></textarea>
                </label>
                @error('quote') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
                <label class="block">
                    <span class="sr-only">Contexte</span>
                    <input type="text" wire:model="context" placeholder="Contexte : à quelle question répondait-elle ou répondait-il ?"
                           class="min-h-8 w-full border-0 p-0 text-subtle outline-none">
                </label>
            </div>

            <div class="flex flex-wrap gap-2 px-5 pb-3.5">
                <label class="{{ $chip }} {{ $publicFigureId ? $chipOn : $chipOff }}">
                    <x-glyph name="user" :size="16" />
                    <span class="sr-only">Personnalité</span>
                    <select wire:model.live="publicFigureId" class="max-w-48 cursor-pointer bg-transparent outline-none">
                        <option value="">Personnalité</option>
                        @foreach ($figures as $figure)
                            <option value="{{ $figure->id }}">{{ $figure->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $chip }} {{ $statedOn ? $chipOn : $chipOff }}">
                    <x-glyph name="calendar" :size="16" />
                    <span>Déclarée le</span>
                    <input type="date" wire:model.live="statedOn" max="{{ today()->toDateString() }}" class="bg-transparent outline-none">
                </label>
                <div class="inline-flex gap-1" role="radiogroup" aria-label="Type">
                    @foreach (\App\Enums\CardType::cases() as $option)
                        <label class="{{ $chip }} cursor-pointer {{ $type === $option->value ? $chipOn : $chipOff }}">
                            <input type="radio" wire:model.live="type" value="{{ $option->value }}" class="sr-only">
                            <x-glyph name="tag" :size="16" />
                            {{ $option->label() }}
                        </label>
                    @endforeach
                </div>
                <label class="{{ $chip }} {{ $deadline ? $chipOn : $chipOff }}">
                    <x-glyph name="clock" :size="16" />
                    <span>Échéance</span>
                    <input type="date" wire:model.live="deadline" class="bg-transparent outline-none">
                </label>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = ! open" class="{{ $chip }} cursor-pointer {{ $themes ? $chipOn : $chipOff }}">
                        <span aria-hidden="true">#</span>
                        Thèmes
                        @if ($themes)
                            ({{ count($themes) }})
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute left-0 z-10 mt-1 flex max-h-72 w-60 flex-col overflow-y-auto rounded-lg border border-line bg-white p-1 shadow-lg">
                        @foreach ($allThemes as $theme)
                            <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md px-2 text-[13px] hover:bg-sidebar">
                                <input type="checkbox" wire:model.live="themes" value="{{ $theme->id }}">
                                {{ $theme->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @if ($errors->hasAny(['publicFigureId', 'statedOn', 'type', 'deadline', 'themes']))
                <div class="flex flex-col px-5 pb-3 text-xs text-negative-text">
                    @foreach (['publicFigureId', 'statedOn', 'type', 'deadline', 'themes'] as $name)
                        @error($name) <span>{{ $message }}</span> @enderror
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap gap-2 px-5 pb-1.5">
                <label class="{{ $field }} flex-[4_1_280px]">
                    <x-glyph name="link" :size="16" stroke="#6F6F6F" :width="2" />
                    <span class="sr-only">Lien vers la source</span>
                    <input type="url" wire:model="sourceUrl" placeholder="Lien vers la source" class="min-w-0 flex-1 outline-none">
                </label>
                <label class="{{ $field }} flex-[1_1_120px]">
                    <span class="sr-only">Nature de la source</span>
                    <select wire:model="sourceKind" class="min-w-0 flex-1 bg-transparent outline-none">
                        @foreach (\App\Enums\SourceKind::cases() as $kind)
                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $field }} flex-[1_1_100px]">
                    <span class="sr-only">Horodatage dans la vidéo ou l’audio</span>
                    <input type="text" wire:model="timecode" placeholder="à 12:34" class="min-w-0 flex-1 outline-none">
                </label>
            </div>
            @foreach (['sourceUrl', 'timecode'] as $name)
                @error($name) <span class="px-5 text-xs text-negative-text">{{ $message }}</span> @enderror
            @endforeach
            <p class="px-5 pb-4 text-xs text-muted">Une copie archivée de la page est créée à l’envoi.</p>

            <div class="flex flex-col gap-2.5 border-t border-line bg-sidebar px-5 py-3.5">
                @guest
                    <span class="text-xs font-semibold text-muted">Vous n’êtes pas connecté · <a href="{{ route('login') }}" wire:navigate>Se connecter</a></span>
                    <label class="{{ $field }} bg-white">
                        <span class="text-[13px] whitespace-nowrap text-subtle">Votre e-mail</span>
                        <input type="email" wire:model="email" placeholder="vous@exemple.fr" class="min-w-0 flex-1 outline-none">
                    </label>
                    @error('email') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
                    <p class="text-xs leading-normal text-muted">Un lien de confirmation vous sera envoyé. L’adresse sert seulement à vous informer du sort de la carte et à vous proposer un compte.</p>
                @endguest
                <label class="flex items-start gap-2 text-[13px] leading-normal">
                    <input type="checkbox" wire:model="charter" class="mt-[3px]">
                    <span>J’ai lu la <a href="{{ route('pages.charter') }}" target="_blank">charte du contributeur</a> et la citation est exacte.</span>
                </label>
                @error('charter') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
                {{-- Étape 6 : vérification anti-robot sans cookie (ALTCHA). --}}
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('home') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-md bg-[#F2F2F2] px-4 font-semibold text-ink no-underline hover:text-ink">Annuler</a>
                <button type="submit" wire:loading.attr="disabled" class="min-h-11 cursor-pointer rounded-md bg-accent px-4 font-semibold text-white disabled:opacity-60">Envoyer pour relecture</button>
            </div>
        </form>

        <p class="max-w-[680px] text-center text-[13px] leading-relaxed text-[#4A4A4A]">
            Raccourci : <strong class="font-semibold">Q</strong> ouvre l’ajout rapide depuis n’importe quelle page. La citation, elle, n’est jamais interprétée.
        </p>
    @endif
</div>
