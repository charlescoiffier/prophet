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

/**
 * Valide le verdict proposé : la carte est tranchée.
 */
class ApproveVerdict
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Verdict $verdict, User $actor): Verdict
    {
        Gate::forUser($actor)->authorize('approve', $verdict);

        DB::transaction(function () use ($verdict, $actor) {
            $verdict->status = VerdictStatus::Approved;
            $verdict->decider()->associate($actor);
            $verdict->decided_at = now();
            $verdict->save();

            $card = $verdict->card;
            $cardBefore = $card->status;
            $card->status = CardStatus::Closed;
            $card->save();

            $this->recordActivity->handle(
                $verdict, $actor, ActivityAction::Approved,
                ['status' => VerdictStatus::Proposed->value], ['status' => VerdictStatus::Approved->value],
            );
            $this->recordActivity->handle(
                $card, $actor, ActivityAction::Approved,
                ['status' => $cardBefore->value], ['status' => $card->status->value, 'verdict' => $verdict->value->value],
            );
        });

        $verdict->proposer->notify(new VerdictDecided($verdict));

        return $verdict;
    }
}
