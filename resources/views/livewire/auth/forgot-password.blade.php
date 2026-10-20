<x-dialog-card title="Mot de passe oublié">
    @if ($sent)
        <p role="status">{{ __('api.password_link_sent') }}</p>
        <a href="{{ route('login') }}" wire:navigate class="text-[13px]">Retour à la connexion</a>
    @else
        <form wire:submit="send" class="flex flex-col gap-3">
            <x-text-field label="Adresse e-mail du compte" name="email" type="email" autocomplete="email" autofocus required />
            <button type="submit" class="min-h-11 cursor-pointer bg-accent font-semibold text-white">Recevoir un lien</button>
        </form>
    @endif
</x-dialog-card>
