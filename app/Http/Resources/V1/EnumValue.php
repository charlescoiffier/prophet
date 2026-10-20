<?php

namespace App\Http\Resources\V1;

use BackedEnum;

/**
 * Une valeur d'enum exposée avec son code stable et son libellé français.
 */
class EnumValue
{
    /**
     * @return array{value: string, label: string}|null
     */
    public static function of(?BackedEnum $enum): ?array
    {
        if ($enum === null) {
            return null;
        }

        return ['value' => (string) $enum->value, 'label' => $enum->label()];
    }
}
