<?php

return [
    'role' => [
        'member' => 'Membre',
        'moderator' => 'Modérateur',
        'admin' => 'Administrateur',
    ],
    'card_type' => [
        'promise' => 'Promesse',
        'prediction' => 'Prédiction',
    ],
    'card_status' => [
        'awaiting_email' => 'En attente de confirmation de l’e-mail',
        'in_moderation' => 'En modération',
        'published' => 'Publiée',
        'verdict_proposed' => 'Verdict proposé',
        'closed' => 'Tranchée',
        'rejected' => 'Refusée',
    ],
    'deadline_nature' => [
        'explicit' => 'Explicite',
        'inferred' => 'Déduite',
    ],
    'public_figure_category' => [
        'politician' => 'Politique',
        'journalist' => 'Journaliste',
        'columnist' => 'Éditorialiste',
        'economist' => 'Économiste',
        'other' => 'Autre',
    ],
    'source_kind' => [
        'article' => 'Article',
        'video' => 'Vidéo',
        'audio' => 'Audio',
        'programme' => 'Programme',
        'social_media' => 'Réseau social',
        'official_journal' => 'Journal officiel',
        'public_data' => 'Donnée publique',
    ],
    'verdict_value' => [
        'kept' => 'Tenue',
        'partially_kept' => 'Partiellement tenue',
        'compromised' => 'Compromis',
        'abandoned' => 'Abandonnée',
        'moot' => 'Caduque',
        'correct' => 'Juste',
        'partially_correct' => 'Partiellement juste',
        'wrong' => 'Fausse',
        'unverifiable' => 'Invérifiable',
    ],
    'verdict_status' => [
        'proposed' => 'Proposé',
        'approved' => 'Validé',
        'rejected' => 'Refusé',
    ],
    'report_status' => [
        'open' => 'Ouvert',
        'resolved' => 'Traité',
        'dismissed' => 'Rejeté',
    ],
    'activity_action' => [
        'proposed' => 'Proposition',
        'email_confirmed' => 'Confirmation de l’e-mail',
        'approved' => 'Validation',
        'rejected' => 'Refus',
        'edited' => 'Modification',
        'reported' => 'Signalement',
    ],
];
