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
    case ReportResolved = 'report_resolved';
    case ReportDismissed = 'report_dismissed';
    case SourceAdded = 'source_added';
    case SourceRemoved = 'source_removed';
    case RoleChanged = 'role_changed';
}
