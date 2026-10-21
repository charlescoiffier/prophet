<?php

namespace App\Support;

use App\Models\Affiliation;
use Illuminate\Support\Collection;

/**
 * Noms d'organisations (partis, médias, gouvernements) : éviter les doublons dus à une faute de frappe.
 */
class Organizations
{
    /**
     * @return Collection<int, string> organisations déjà connues, par ordre alphabétique
     */
    public static function known(): Collection
    {
        return Affiliation::query()->distinct()->orderBy('organization')->pluck('organization');
    }

    /**
     * Nom déjà connu qui ne diffère que par la casse, les accents ou la ponctuation (« parti a » → « Parti A »).
     */
    public static function canonical(string $name): string
    {
        $normalized = Names::normalize($name);

        return self::known()->first(fn (string $known) => Names::normalize($known) === $normalized) ?? trim($name);
    }

    public static function isKnown(string $name): bool
    {
        $normalized = Names::normalize($name);

        return self::known()->contains(fn (string $known) => Names::normalize($known) === $normalized);
    }

    /**
     * Choix proposés pendant la saisie : les organisations connues qui contiennent le texte tapé,
     * puis ce texte comme nouvelle organisation s'il n'existe pas encore.
     *
     * @return array<string, string>
     */
    public static function suggestions(string $search): array
    {
        $search = trim($search);
        $normalized = Names::normalize($search);

        $matches = self::known()
            ->filter(fn (string $known) => $normalized === '' || str_contains(Names::normalize($known), $normalized))
            ->take(50)
            ->mapWithKeys(fn (string $known) => [$known => $known])
            ->all();

        if ($search !== '' && ! self::isKnown($search)) {
            $matches[$search] = $search.' (nouvelle organisation)';
        }

        return $matches;
    }

    /**
     * Noms connus très proches (faute de frappe probable) : une ou deux lettres d'écart,
     * sur des noms assez longs pour que ce ne soit pas une autre organisation (« Parti A » / « Parti B »).
     *
     * @return list<string>
     */
    public static function nearDuplicates(string $name): array
    {
        $normalized = Names::normalize($name);

        return self::known()
            ->filter(function (string $known) use ($normalized) {
                $other = Names::normalize($known);
                if ($other === $normalized || min(strlen($other), strlen($normalized)) < 8) {
                    return false;
                }
                $distance = levenshtein($other, $normalized);

                return $distance <= 2 && $distance / max(strlen($other), strlen($normalized)) <= 0.15;
            })
            ->values()
            ->all();
    }
}
