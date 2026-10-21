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

    /**
     * Coupe un nom complet au premier espace : « Jeanne Exemple » → [« Jeanne », « Exemple »],
     * « Marie de La Tour » → [« Marie », « de La Tour »] ; un seul mot est un nom sans prénom.
     *
     * @return array{0: string|null, 1: string}
     */
    public static function split(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2);

        return count($parts) === 2 ? [$parts[0], $parts[1]] : [null, $parts[0]];
    }

    /**
     * Nom affiché : « Prénom Nom », ou le nom seul.
     */
    public static function full(?string $firstName, ?string $lastName): string
    {
        return trim(trim((string) $firstName).' '.trim((string) $lastName));
    }
}
