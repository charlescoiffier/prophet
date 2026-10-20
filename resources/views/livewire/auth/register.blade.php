<x-dialog-card title="Créer un compte">
    <p class="text-[13px] leading-relaxed text-subtle">
        Un compte permet de proposer des verdicts et de suivre vos propositions. Si vous avez déjà proposé des cartes avec la même adresse, elles vous seront rattachées dès que vous l’aurez confirmée.
    </p>
    <form wire:submit="register" class="flex flex-col gap-3">
        <x-text-field label="Pseudo (affiché publiquement)" name="username" autocomplete="username" required />
        <x-text-field label="Adresse e-mail (jamais publiée)" name="email" type="email" autocomplete="email" required />
        <x-text-field label="Mot de passe" name="password" type="password" autocomplete="new-password" required />
        <x-text-field label="Confirmer le mot de passe" name="password_confirmation" type="password" autocomplete="new-password" required />
        <button type="submit" class="min-h-11 cursor-pointer rounded-md bg-accent font-semibold text-white">Créer mon compte</button>
    </form>
    <p class="text-[13px]">Déjà inscrit ? <a href="{{ route('login') }}" wire:navigate>Se connecter</a></p>
</x-dialog-card>
