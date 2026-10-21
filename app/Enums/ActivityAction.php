<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActivityAction: string implements HasLabel
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
    case AccountDeleted = 'account_deleted';
    case AccountRestored = 'account_restored';
    case CategoryCreated = 'category_created';
    case ThemeCreated = 'theme_created';
}
