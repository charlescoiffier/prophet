<?php

namespace App\Actions\Reports;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Clôt un signalement : traité (l'erreur est corrigée) ou rejeté.
 */
class HandleReport
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Report $report, User $actor, ReportStatus $outcome, ?string $comment = null): Report
    {
        Gate::forUser($actor)->authorize('handle', $report);

        if ($outcome === ReportStatus::Open) {
            throw new InvalidArgumentException('Un signalement se clôt comme traité ou rejeté.');
        }

        DB::transaction(function () use ($report, $actor, $outcome, $comment) {
            $report->status = $outcome;
            $report->handler()->associate($actor);
            $report->handled_at = now();
            $report->save();

            $this->recordActivity->handle(
                $report->card, $actor,
                $outcome === ReportStatus::Resolved ? ActivityAction::ReportResolved : ActivityAction::ReportDismissed,
                // Le texte du signalement reste privé : seule la note du modérateur est publique.
                comment: $comment,
            );
        });

        return $report;
    }
}
