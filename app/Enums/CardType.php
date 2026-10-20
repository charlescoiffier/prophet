<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CardType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Promise = 'promise';
    case Prediction = 'prediction';
}
