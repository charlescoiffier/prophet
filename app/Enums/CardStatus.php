<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CardStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case AwaitingEmail = 'awaiting_email';
    case InModeration = 'in_moderation';
    case Published = 'published';
    case VerdictProposed = 'verdict_proposed';
    case Closed = 'closed';
    case Rejected = 'rejected';

    /**
     * Statuts visibles du public.
     *
     * @return list<self>
     */
    public static function public(): array
    {
        return [self::Published, self::VerdictProposed, self::Closed];
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AwaitingEmail => 'gray',
            self::InModeration => 'info',
            self::Published => 'primary',
            self::VerdictProposed => 'warning',
            self::Closed => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isPublic(): bool
    {
        return in_array($this, self::public(), true);
    }
}
