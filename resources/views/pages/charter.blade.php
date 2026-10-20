{{-- Texte de travail, à relire par Charles avant l'ouverture des propositions. --}}
<x-page title="Charte du contributeur" description="Les règles pour proposer une carte ou un verdict.">
    <article class="mx-auto flex max-w-[680px] flex-col gap-4 bg-field p-6 leading-relaxed shadow-[0_16px_48px_rgba(0,0,0,0.18)] sm:p-8">
        <a href="{{ route('home') }}" class="text-[13px]">← Toutes les cartes</a>
        <h1 class="font-serif text-[40px] leading-none font-extrabold tracking-[-1px]">Charte du contributeur</h1>
        <p>{{ config('app.name') }} recense des promesses et des prédictions de personnalités publiques pour les confronter, le moment venu, aux faits. Chaque proposition est relue par un modérateur avant publication.</p>
        <h2 class="pt-2 text-base font-bold">En proposant une carte, je m’engage à :</h2>
        <ul class="list-disc space-y-1 pl-5">
            <li>citer <strong>mot pour mot</strong>, sans couper le sens de la phrase ;</li>
            <li>donner une source vérifiable, avec l’horodatage pour une vidéo ou un audio ;</li>
            <li>dater la déclaration et décrire honnêtement son contexte ;</li>
            <li>ne proposer que des engagements ou des prédictions vérifiables par des faits.</li>
        </ul>
        <h2 class="pt-2 text-base font-bold">En proposant un verdict, je m’engage à :</h2>
        <ul class="list-disc space-y-1 pl-5">
            <li>juger des <strong>faits</strong>, jamais des intentions ;</li>
            <li>appuyer le verdict sur au moins une source publique, de préférence une donnée officielle ;</li>
            <li>suivre le critère de verdict écrit sur la carte.</li>
        </ul>
        <h2 class="pt-2 text-base font-bold">Ce que font les modérateurs</h2>
        <p>Ils vérifient la citation et la source, peuvent préciser la rédaction (l’historique public garde la trace de chaque modification), publient ou refusent avec un motif. Personne ne valide sa propre proposition.</p>
        <p class="text-[13px] text-muted">Voir aussi : <a href="{{ route('pages.privacy') }}">données personnelles</a>.</p>
    </article>
</x-page>
