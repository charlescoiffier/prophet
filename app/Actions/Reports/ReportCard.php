<?php

namespace App\Actions\Reports;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\ReportStatus;
use App\Models\Card;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Signale une erreur sur une carte publiée. Le texte du signalement reste
 * privé (il peut contenir des données personnelles) ; l'historique public
 * n'indique que l'existence du signalement.
 */
class ReportCard
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Card $card, ?User $actor, string $reason, ?string $email = null): Report
    {
        Gate::forUser($actor)->authorize('create', [Report::class, $card]);

        $reason = trim($reason);
        $email = $actor === null ? trim((string) $email) : null;

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => __('validation.custom.report.reason')]);
        }

        if ($actor === null && $email === '') {
            throw ValidationException::withMessages(['email' => __('validation.custom.email.required_for_guest')]);
        }

        return DB::transaction(function () use ($card, $actor, $reason, $email) {
            $report = new Report(['reason' => $reason, 'email' => $email]);
            $report->card()->associate($card);
            $report->user()->associate($actor);
            $report->status = ReportStatus::Open;
            $report->save();

            $this->recordActivity->handle($card, $actor, ActivityAction::Reported);

            return $report;
        });
    }
}
