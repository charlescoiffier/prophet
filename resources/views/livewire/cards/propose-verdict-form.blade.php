<section class="flex flex-col gap-3 border border-line p-4">
    @if (! $open)
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-[13px] text-subtle">L’échéance est passée, ou la {{ mb_strtolower($card->type->label()) }} est déjà tranchée par les faits ?</p>
            <button type="button" wire:click="$set('open', true)" class="min-h-11 cursor-pointer bg-accent px-4 font-semibold text-white">Proposer un verdict</button>
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-3">
            <h2 class="text-sm font-bold">Proposer un verdict</h2>
            <fieldset class="flex flex-wrap gap-2">
                <legend class="sr-only">Verdict</legend>
                @foreach ($values as $option)
                    <label @class([
                        'flex min-h-11 cursor-pointer items-center border px-3 text-[13px]',
                        'border-accent-line bg-accent-soft font-semibold text-accent-dark' => $value === $option->value,
                        'border-line-strong text-subtle' => $value !== $option->value,
                    ])>
                        <input type="radio" wire:model.live="value" value="{{ $option->value }}" class="sr-only">
                        {{ $option->label() }}
                    </label>
                @endforeach
            </fieldset>
            @error('value') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror

            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-muted">Justification : les faits, chiffres à l’appui</span>
                <textarea wire:model="justification" rows="4" class="border border-line-strong p-2 outline-none focus:border-accent"></textarea>
                @error('justification') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
            </label>

            <div class="flex flex-wrap gap-2">
                <label class="flex min-h-11 flex-[4_1_260px] items-center gap-2 border border-line-strong px-3 focus-within:border-accent">
                    <x-uicon name="link" :size="16" />
                    <span class="sr-only">Lien vers la source</span>
                    <input type="url" wire:model="sourceUrl" placeholder="Lien vers la source du verdict" class="min-w-0 flex-1 outline-none">
                </label>
                <label class="flex min-h-11 flex-[1_1_140px] items-center border border-line-strong px-2 focus-within:border-accent">
                    <span class="sr-only">Nature de la source</span>
                    <select wire:model="sourceKind" class="min-w-0 flex-1 bg-transparent outline-none">
                        @foreach (\App\Enums\SourceKind::cases() as $kind)
                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex min-h-11 flex-[1_1_140px] items-center border border-line-strong px-3 focus-within:border-accent">
                    <span class="sr-only">Média ou organisme</span>
                    <input type="text" wire:model="sourceOutlet" placeholder="Média ou organisme" class="min-w-0 flex-1 outline-none">
                </label>
            </div>
            @error('sourceUrl') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
            <p class="text-xs text-muted">Une copie archivée de la page est créée à l’envoi. Un modérateur relira votre proposition.</p>

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('open', false)" class="min-h-11 cursor-pointer border border-ink bg-transparent px-4 font-semibold">Annuler</button>
                <button type="submit" class="min-h-11 cursor-pointer bg-accent px-4 font-semibold text-white">Envoyer pour relecture</button>
            </div>
        </form>
    @endif
</section>
