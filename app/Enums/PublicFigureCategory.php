<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PublicFigureCategory: string implements HasLabel
{
    use HasTranslatedLabel;

    case Politician = 'politician';
    case Journalist = 'journalist';
    case Columnist = 'columnist';
    case Economist = 'economist';
    case Other = 'other';
}
