<?php

namespace App\Enums;

enum VerdictStatus: string
{
    use HasTranslatedLabel;

    case Proposed = 'proposed';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
