<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VerdictStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Proposed = 'proposed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getColor(): string
    {
        return match ($this) {
            self::Proposed => 'warning',
            self::Approved => 'success',
            self::Rejected => 'gray',
        };
    }
}
