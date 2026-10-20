@php
    $label = 'mb-2 block text-xs font-semibold';
    $field = 'w-full border border-line-strong bg-field px-3 py-3 text-[13px] text-ink outline-none focus:border-ink';
    $hint = 'mt-1.5 text-[11px] leading-relaxed text-muted';
    $error = 'mt-1.5 block text-xs text-negative-text';
    $section = 'border-t border-ink py-6';
    $heading = 'mb-5 font-serif text-[27px] leading-none font-extrabold';
@endphp
<div class="flex flex-col">
    <header class="pt-7 pb-6 lg:pt-[35px] lg:pb-[30px]">
        <a href="{{ route('home') }}" wire:navigate class="text-[11px] text-muted no-underline hover:text-ink">← Toutes les cartes</a>
        <h1 class="mt-6 font-serif text-[clamp(40px,4.4vw,60px)] leading-[0.97] font-extrabold tracking-[-2px]">Les mots engagent.<br>Gardons-en la trace.</h1>
        <p class="mt-4 max-w-[580px] text-[13px] leading-relaxed text-subtle">Une promesse précise, une prédiction vérifiable ? Rassemblez la citation, sa source et son échéance : un modérateur relira la carte avant sa publication.</p>
    </header>

    @if ($sent)
        <section role="status" class="flex max-w-[760px] flex-col gap-4 border border-ink bg-field p-6">
            <h2 class="font-serif text-[30px] leading-[1.05] font-extrabold">
                {{ $sent->author_id ? 'Merci, votre carte part en relecture.' : 'Merci ! Vérifiez votre boîte de réception.' }}
            </h2>
            <blockquote class="border-l-[3px] border-ink pl-3 font-serif text-xl font-bold">{{ $sent->quote }}</blockquote>
            <p class="text-[13px] leading-relaxed">
                @if ($sent->author_id)
                    Un modérateur va vérifier la citation et la source. Vous serez prévenu par e-mail.
                @else
                    Nous vous avons envoyé un lien de confirmation : votre proposition partira en relecture dès que vous aurez cliqué dessus.
                @endif
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" wire:navigate class="inline-flex min-h-11 items-center border border-ink px-4 text-[13px] font-semibold text-ink no-underline hover:text-ink">Retour aux cartes</a>
                <a href="{{ route('cards.propose') }}" class="inline-flex min-h-11 items-center bg-accent px-4 text-[13px] font-semibold text-white no-underline hover:text-white">Proposer une autre carte</a>
            </div>
        </section>
    @else
        <div class="grid grid-cols-1 gap-[38px] lg:grid-cols-[minmax(0,1fr)_280px]">
            <form wire:submit="submit" class="flex flex-col">
                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">La déclaration</h2>

                    <fieldset class="mb-5">
                        <legend class="{{ $label }}">De quoi s’agit-il ?</legend>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach (\App\Enums\CardType::cases() as $option)
                                <label @class([
                                    'flex min-h-12 cursor-pointer items-center gap-2.5 border px-3 text-[13px]',
                                    'border-ink bg-signal-yellow font-semibold' => $type === $option->value,
                                    'border-ink bg-field' => $type !== $option->value,
                                ])>
                                    <input type="radio" wire:model.live="type" value="{{ $option->value }}" class="accent-accent">
                                    <x-uicon :name="\App\View\Appearance::typeIcon($option)" :size="18" />
                                    Une {{ mb_strtolower($option->label()) }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="mb-5">
                        <label for="quote" class="{{ $label }}">Citation exacte *</label>
                        <textarea id="quote" wire:model="quote" rows="3" placeholder="« Nous nous engageons à… »"
                                  class="{{ $field }} resize-none font-serif text-xl leading-snug font-bold"></textarea>
                        <p class="{{ $hint }}">Reprenez les mots prononcés, sans les reformuler ni les couper.</p>
                        @error('quote') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label for="figure" class="{{ $label }}">Personnalité *</label>
                        <select id="figure" wire:model="publicFigureId" class="{{ $field }}">
                            <option value="">Choisir une personnalité</option>
                            @foreach ($figures as $figure)
                                <option value="{{ $figure->id }}">{{ $figure->name }}</option>
                            @endforeach
                        </select>
                        <p class="{{ $hint }}">Elle n’est pas dans la liste ? Indiquez son nom et sa fonction dans le contexte : un modérateur créera sa fiche.</p>
                        @error('publicFigureId') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="context" class="{{ $label }}">Contexte</label>
                        <textarea id="context" wire:model="context" rows="3" placeholder="À quelle question répondait-elle ou répondait-il ? Où, devant qui ?"
                                  class="{{ $field }} resize-none"></textarea>
                    </div>
                </section>

                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">La source &amp; le calendrier</h2>

                    <div class="mb-5">
                        <label for="source" class="{{ $label }}">Lien vers la source originale *</label>
                        <input id="source" type="url" wire:model="sourceUrl" placeholder="https://…" class="{{ $field }}">
                        <p class="{{ $hint }}">Interview, vidéo, discours, publication : de première main de préférence. Une copie archivée est créée à l’envoi.</p>
                        @error('sourceUrl') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="kind" class="{{ $label }}">Nature de la source</label>
                            <select id="kind" wire:model="sourceKind" class="{{ $field }}">
                                @foreach (\App\Enums\SourceKind::cases() as $kind)
                                    <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="timecode" class="{{ $label }}">Horodatage (vidéo ou audio)</label>
                            <input id="timecode" type="text" wire:model="timecode" placeholder="12:34" class="{{ $field }}">
                            @error('timecode') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="stated" class="{{ $label }}">Date de la déclaration *</label>
                            <input id="stated" type="date" wire:model="statedOn" max="{{ today()->toDateString() }}" class="{{ $field }}">
                            @error('statedOn') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="deadline" class="{{ $label }}">Échéance annoncée</label>
                            <input id="deadline" type="date" wire:model="deadline" class="{{ $field }}">
                            <p class="{{ $hint }}">La date à laquelle on pourra trancher.</p>
                        </div>
                    </div>
                </section>

                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">Le classement</h2>
                    <fieldset>
                        <legend class="{{ $label }}">Thèmes (cinq au plus)</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($allThemes as $theme)
                                <label @class([
                                    'flex min-h-10 cursor-pointer items-center gap-1.5 border px-3 text-[13px]',
                                    'border-ink bg-ink text-paper' => in_array($theme->id, $themes, true),
                                    'border-line-strong bg-field hover:border-ink' => ! in_array($theme->id, $themes, true),
                                ])>
                                    <input type="checkbox" wire:model.live="themes" value="{{ $theme->id }}" class="sr-only">
                                    <x-uicon name="theme" :size="12" /> {{ $theme->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('themes') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </fieldset>
                </section>

                <section class="{{ $section }}">
                    <h2 class="{{ $heading }}">Vous</h2>
                    @guest
                        <div class="mb-5">
                            <label for="email" class="{{ $label }}">Votre adresse e-mail *</label>
                            <input id="email" type="email" wire:model="email" placeholder="vous@exemple.fr" class="{{ $field }}">
                            <p class="{{ $hint }}">Un lien de confirmation vous sera envoyé. L’adresse sert seulement à vous informer du sort de la carte et à vous proposer un compte ; elle n’est jamais publiée. Déjà inscrit ? <a href="{{ route('login') }}" wire:navigate>Se connecter</a></p>
                            @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                    @endguest
                    <label class="mb-5 flex items-start gap-2.5 text-[13px] leading-normal">
                        <input type="checkbox" wire:model="charter" class="mt-[3px] accent-accent">
                        <span>J’ai lu la <a href="{{ route('pages.charter') }}" target="_blank">charte du contributeur</a> et la citation est exacte.</span>
                    </label>
                    @error('charter') <span class="{{ $error }} -mt-3 mb-5">{{ $message }}</span> @enderror
                    @guest
                        <x-altcha />
                    @endguest
                </section>

                <div class="border-t border-ink pt-6">
                    <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 cursor-pointer items-center bg-accent px-5 text-[13px] font-semibold text-white hover:bg-accent-dark disabled:opacity-60">
                        Proposer cette carte ↗
                    </button>
                    <p class="{{ $hint }}">La carte sera relue avant sa publication. Raccourci : <kbd class="border border-line-strong px-1">Q</kbd> ouvre cette page depuis n’importe où.</p>
                </div>
            </form>

            <aside class="self-start border border-ink bg-signal-yellow p-6 lg:sticky lg:top-6">
                <h2 class="font-serif text-[30px] leading-[1.05] font-extrabold">Une bonne carte commence par une bonne source.</h2>
                <p class="mt-4 text-xs leading-relaxed">Une citation exacte, attribuée et datée.</p>
                <hr class="my-5 border-ink/30">
                <p class="text-xs leading-relaxed"><b>Vérifiable</b><br>Un engagement ou un résultat que l’on pourra confronter aux faits.</p>
                <p class="mt-3 text-xs leading-relaxed"><b>Contextualisée</b><br>Le passage complet, pour comprendre ce qui a réellement été dit.</p>
                <p class="mt-3 text-xs leading-relaxed"><b>Sourcée</b><br>Un lien consultable ; la copie archivée est faite pour vous.</p>
                <hr class="my-5 border-ink/30">
                <p class="text-xs leading-relaxed">On juge des faits, jamais des intentions : une promesse trop vague sera refusée, avec son motif.</p>
            </aside>
        </div>
    @endif
</div>
