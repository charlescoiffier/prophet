<?php

namespace App\Support;

use Illuminate\Support\Str;

class Names
{
    /**
     * Forme de comparaison d'un nom : sans casse, sans accents, sans ponctuation ni espaces multiples.
     */
    public static function normalize(string $name): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($name))));
    }
}
