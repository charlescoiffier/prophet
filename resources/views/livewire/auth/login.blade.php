<x-dialog-card title="Se connecter">
    @if (session('status'))
        <p role="status" class="rounded-md bg-accent-soft px-3 py-2.5 text-[13px] text-accent-dark">{{ session('status') }}</p>
    @endif
    <form wire:submit="login" class="flex flex-col gap-3">
        <x-text-field label="Adresse e-mail" name="email" type="email" autocomplete="email" autofocus required />
        <x-text-field label="Mot de passe" name="password" type="password" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-[13px]">
            <input type="checkbox" wire:model="remember"> Rester connecté
        </label>
        <button type="submit" class="min-h-11 cursor-pointer rounded-md bg-accent font-semibold text-white">Se connecter</button>
    </form>
    <div class="flex flex-wrap justify-between gap-2 text-[13px]">
        <a href="{{ route('password.request') }}" wire:navigate>Mot de passe oublié ?</a>
        <a href="{{ route('register') }}" wire:navigate>Créer un compte</a>
    </div>
</x-dialog-card>
