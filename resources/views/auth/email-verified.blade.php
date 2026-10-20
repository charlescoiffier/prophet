<x-page title="Adresse confirmée" noindex>
    <x-dialog-card title="Bienvenue, {{ $user->username }} : votre adresse est confirmée.">
        <p class="leading-relaxed">Vous pouvez maintenant proposer des cartes et des verdicts. Les cartes que vous aviez proposées avec cette adresse sont rattachées à votre compte.</p>
        <a href="{{ route('account.cards') }}" class="text-[13px]">Voir mes propositions</a>
    </x-dialog-card>
</x-page>
