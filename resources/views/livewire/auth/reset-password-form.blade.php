<x-dialog-card title="Nouveau mot de passe">
    <form wire:submit="save" class="flex flex-col gap-3">
        <x-text-field label="Adresse e-mail" name="email" type="email" autocomplete="email" required />
        <x-text-field label="Nouveau mot de passe" name="password" type="password" autocomplete="new-password" autofocus required />
        <x-text-field label="Confirmer le mot de passe" name="password_confirmation" type="password" autocomplete="new-password" required />
        <button type="submit" class="min-h-11 cursor-pointer rounded-md bg-accent font-semibold text-white">Enregistrer</button>
    </form>
</x-dialog-card>
