<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Libellé français d'un cas d'enum, lu dans lang/fr/enums.php
 * sous la clé « enums.<nom_de_l_enum>.<valeur> ».
 */
trait HasTranslatedLabel
{
    public function label(): string
    {
        return __('enums.'.Str::snake(class_basename(static::class)).'.'.$this->value);
    }

    /**
     * @return array<string, string> valeur => libellé, pour les listes déroulantes.
     */
    public static function options(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
