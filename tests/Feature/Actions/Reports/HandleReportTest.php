<?php

use App\Actions\Reports\HandleReport;
use App\Enums\ActivityAction;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

it('clôt le signalement et l’inscrit à l’historique de la carte', function (ReportStatus $outcome, ActivityAction $action) {
    $report = Report::factory()->create();
    $moderator = User::factory()->moderator()->create();

    app(HandleReport::class)->handle($report, $moderator, $outcome, 'Lien corrigé.');

    expect($report->refresh())
        ->status->toBe($outcome)
        ->handled_by->toBe($moderator->id)
        ->and($report->card->activityLogs()->sole())
        ->action->toBe($action)
        ->comment->toBe('Lien corrigé.');
})->with([
    [ReportStatus::Resolved, ActivityAction::ReportResolved],
    [ReportStatus::Dismissed, ActivityAction::ReportDismissed],
]);
