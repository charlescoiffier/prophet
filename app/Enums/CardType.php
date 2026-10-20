<?php

namespace App\Enums;

enum CardType: string
{
    use HasTranslatedLabel;

    case Promise = 'promise';
    case Prediction = 'prediction';
}
