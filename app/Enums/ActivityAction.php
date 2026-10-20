<?php

namespace App\Enums;

enum ActivityAction: string
{
    use HasTranslatedLabel;

    case Proposed = 'proposed';
    case EmailConfirmed = 'email_confirmed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Edited = 'edited';
    case Reported = 'reported';
}
