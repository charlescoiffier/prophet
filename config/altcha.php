<?php

/*
 * Anti-robot ALTCHA : preuve de travail calculée par le navigateur, sans cookie.
 * Le coût visé est d'environ une seconde pour un visiteur ; les tests le réduisent.
 */
return [
    // Secret HMAC des défis. À défaut, dérivé de APP_KEY.
    'hmac_key' => env('ALTCHA_HMAC_KEY'),

    // Itérations PBKDF2 par essai, et nombre d'essais (tiré au hasard) pour trouver la solution.
    'cost' => (int) env('ALTCHA_COST', 1000),
    'counter_min' => (int) env('ALTCHA_COUNTER_MIN', 500),
    'counter_max' => (int) env('ALTCHA_COUNTER_MAX', 1500),

    // Durée de validité d'un défi, en secondes.
    'expires' => 600,
];
