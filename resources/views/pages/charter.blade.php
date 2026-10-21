{{-- Texte de travail, à relire par Charles avant l'ouverture des propositions. --}}
<x-text-page title="Charte du contributeur"
             lead="Les règles pour proposer une carte ou un verdict. Chaque proposition est relue par un modérateur avant publication.">
    <section>
        <h2>Pourquoi ce registre</h2>
        <p>{{ config('app.name') }} recense des promesses et des prédictions de personnalités publiques pour les confronter, le moment venu, aux faits. Sa valeur tient à sa rigueur : des citations exactes, des sources vérifiables, des verdicts argumentés.</p>
    </section>

    <section>
        <h2>En proposant une carte</h2>
        <ul>
            <li>je cite <strong>mot pour mot</strong>, sans couper le sens de la phrase ;</li>
            <li>je donne une source vérifiable, avec l’horodatage pour une vidéo ou un audio ;</li>
            <li>je date la déclaration et décris honnêtement son contexte ;</li>
            <li>je ne propose que des engagements ou des prédictions vérifiables par des faits.</li>
        </ul>
    </section>

    <section>
        <h2>En proposant un verdict</h2>
        <ul>
            <li>je juge des <strong>faits</strong>, jamais des intentions ;</li>
            <li>j’appuie le verdict sur au moins une source publique, de préférence une donnée officielle ;</li>
            <li>je suis le critère de verdict écrit sur la carte.</li>
        </ul>
    </section>

    <section>
        <h2>Ce que font les modérateurs</h2>
        <p>Ils vérifient la citation et la source, peuvent préciser la rédaction, publient ou refusent avec un motif. L’historique public de chaque carte garde la trace de chaque décision et de chaque modification. Personne ne valide sa propre proposition.</p>
        <p>Voir aussi : <a href="{{ route('pages.privacy') }}">données personnelles</a>.</p>
    </section>

    <x-slot:aside>
        <h2>En bref</h2>
        <p><b>Exacte</b><br>La citation mot pour mot, datée, avec son contexte.</p>
        <p><b>Sourcée</b><br>Un lien consultable ; la copie archivée est faite pour vous.</p>
        <p><b>Vérifiable</b><br>Un engagement ou un pronostic que les faits pourront trancher.</p>
        <p><b>Relue</b><br>Rien n’est publié sans un modérateur, qui ne juge jamais sa propre proposition.</p>
    </x-slot:aside>
</x-text-page>
