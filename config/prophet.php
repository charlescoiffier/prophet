<?php

return [
    /*
     * Bandeau « Version de test » en haut du site public : à couper au lancement (PROPHET_TEST_NOTICE=false).
     */
    'test_notice' => (bool) env('PROPHET_TEST_NOTICE', true),
];
