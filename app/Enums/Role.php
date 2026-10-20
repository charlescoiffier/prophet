<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    use HasTranslatedLabel;

    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
