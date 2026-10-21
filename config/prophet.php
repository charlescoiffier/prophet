<?php

return [
    /*
     * Version affichée en haut du site public, avec le commit de construction (APP_COMMIT, posé par le
     * workflow de déploiement), qui renvoie vers ce commit dans le dépôt.
     */
    'version' => '0.3.0',
    'commit' => env('APP_COMMIT'),
    'repository' => 'https://github.com/charlescoiffier/prophet',
];
