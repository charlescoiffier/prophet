<?php

return [
    'reason' => 'Motif : :reason',
    'invitation' => [
        'line' => 'Créez un compte avec cette adresse e-mail : vos propositions y seront rattachées et vous pourrez proposer des verdicts.',
        'action' => 'Créer un compte',
    ],
    'confirm_card_email' => [
        'subject' => 'Confirmez votre adresse pour envoyer votre carte',
        'line' => 'Vous avez proposé la carte « :quote ». Confirmez votre adresse e-mail pour qu’elle parte en modération.',
        'action' => 'Confirmer mon adresse',
        'expiry' => 'Ce lien est valable :days jours. Sans confirmation, la proposition et votre adresse seront effacées.',
        'privacy' => 'Votre adresse ne sert qu’à vous prévenir de la suite donnée à votre proposition. Elle n’est jamais publiée.',
    ],
    'card_published' => [
        'subject' => 'Votre carte est publiée',
        'line' => 'Votre proposition « :quote » a été relue et publiée.',
    ],
    'card_rejected' => [
        'subject' => 'Votre carte n’a pas été retenue',
        'line' => 'Votre proposition « :quote » n’a pas été publiée.',
    ],
    'card_edited' => [
        'subject' => 'Votre carte a été modifiée',
        'line' => 'Un modérateur a modifié la rédaction de votre carte « :quote ». L’historique de la carte montre ce qui a changé.',
    ],
    'verdict_approved' => [
        'subject' => 'Votre verdict est validé',
        'line' => 'Votre verdict « :value » sur la carte « :quote » a été validé.',
    ],
    'verdict_rejected' => [
        'subject' => 'Votre verdict n’a pas été retenu',
        'line' => 'Votre verdict « :value » sur la carte « :quote » n’a pas été retenu.',
    ],
];
