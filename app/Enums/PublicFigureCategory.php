<?php

namespace App\Enums;

enum PublicFigureCategory: string
{
    use HasTranslatedLabel;

    case Politician = 'politician';
    case Journalist = 'journalist';
    case Columnist = 'columnist';
    case Economist = 'economist';
    case Other = 'other';
}
