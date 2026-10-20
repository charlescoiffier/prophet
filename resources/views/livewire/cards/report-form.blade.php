<div>
    @if ($sent)
        <p class="bg-accent-soft px-3 py-2.5 text-[13px] text-accent-dark">Merci : votre signalement a été transmis aux modérateurs.</p>
    @elseif (! $open)
        <button type="button" wire:click="$set('open', true)" class="min-h-11 w-full cursor-pointer border border-ink bg-field font-semibold hover:border-accent-line">
            Signaler une erreur
        </button>
    @else
        <form wire:submit="submit" class="flex flex-col gap-2 border border-line-strong bg-field p-3">
            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-muted">Quelle erreur ?</span>
                <textarea wire:model="reason" rows="3" required class="border border-line-strong p-2 text-sm outline-none focus:border-accent"
                          placeholder="Citation inexacte, lien mort, mauvaise date…"></textarea>
                @error('reason') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
            </label>
            @guest
                <label class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-muted">Votre e-mail (jamais publié)</span>
                    <input type="email" wire:model="email" required class="min-h-10 border border-line-strong px-2 text-sm outline-none focus:border-accent">
                    @error('email') <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
                </label>
                <x-altcha />
            @endguest
            <div class="flex gap-2">
                <button type="button" wire:click="$set('open', false)" class="min-h-10 flex-1 cursor-pointer border border-ink bg-transparent font-semibold">Annuler</button>
                <button type="submit" class="min-h-10 flex-1 cursor-pointer bg-accent font-semibold text-white">Envoyer</button>
            </div>
        </form>
    @endif
</div>
