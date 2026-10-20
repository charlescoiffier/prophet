<?php

namespace App\Enums;

enum CardStatus: string
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

    public function isPublic(): bool
    {
        return in_array($this, self::public(), true);
    }
}
