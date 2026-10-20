<?php

namespace App\Actions\Verdicts;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Models\User;
use App\Models\Verdict;
use App\Notifications\VerdictDecided;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Refuse le verdict proposé : la carte attend de nouveau un verdict.
 */
class RejectVerdict
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Verdict $verdict, User $actor, string $reason): Verdict
    {
        Gate::forUser($actor)->authorize('reject', $verdict);

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => __('validation.custom.reason.required')]);
        }

        DB::transaction(function () use ($verdict, $actor, $reason) {
            $verdict->status = VerdictStatus::Rejected;
            $verdict->decider()->associate($actor);
            $verdict->decided_at = now();
            $verdict->rejection_reason = $reason;
            $verdict->save();

            $card = $verdict->card;
            $cardBefore = $card->status;
            $card->status = CardStatus::Published;
            $card->save();

            $this->recordActivity->handle(
                $verdict, $actor, ActivityAction::Rejected,
                ['status' => VerdictStatus::Proposed->value], ['status' => VerdictStatus::Rejected->value], $reason,
            );
            $this->recordActivity->handle(
                $card, $actor, ActivityAction::Rejected,
                ['status' => $cardBefore->value], ['status' => $card->status->value], $reason,
            );
        });

        $verdict->proposer->notify(new VerdictDecided($verdict));

        return $verdict;
    }
}
