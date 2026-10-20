<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Card;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isModerator();
    }

    /**
     * Tout le monde peut signaler une erreur sur une carte visible du public.
     */
    public function create(?User $user, Card $card): bool
    {
        return $card->status->isPublic();
    }

    public function handle(User $user, Report $report): bool
    {
        return $user->isModerator() && $report->status === ReportStatus::Open;
    }

    public function delete(User $user, Report $report): bool
    {
        return false;
    }
}
