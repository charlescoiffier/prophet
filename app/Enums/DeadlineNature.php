<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeadlineNature: string implements HasLabel
{
    use HasTranslatedLabel;

    case Explicit = 'explicit';
    case Inferred = 'inferred';
}
