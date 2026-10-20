<?php

namespace App\Enums;

enum ReportStatus: string
{
    use HasTranslatedLabel;

    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
