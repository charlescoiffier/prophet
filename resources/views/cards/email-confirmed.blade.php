<x-page title="Adresse confirmée" noindex>
    <x-dialog-card title="Merci, votre adresse est confirmée.">
        <blockquote class="border-l-[3px] border-accent pl-3 text-subtle">{{ $card->quote }}</blockquote>
        <p class="leading-relaxed">Votre proposition est entre les mains des modérateurs. Vous recevrez un e-mail dès qu’elle sera publiée, ou si elle n’est pas retenue.</p>
        <p class="text-[13px] leading-relaxed text-muted">Avec un compte créé sous la même adresse, vous pourrez suivre vos propositions et proposer des verdicts. <a href="{{ route('register') }}">Créer un compte</a></p>
        <a href="{{ route('home') }}" class="text-[13px]">Retour aux cartes</a>
    </x-dialog-card>
</x-page>
