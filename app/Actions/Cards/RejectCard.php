<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\CardRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectCard
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Card $card, User $actor, string $reason): Card
    {
        Gate::forUser($actor)->authorize('reject', $card);

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => __('validation.custom.reason.required')]);
        }

        DB::transaction(function () use ($card, $actor, $reason) {
            $before = $card->status;

            $card->status = CardStatus::Rejected;
            $card->rejection_reason = $reason;
            $card->save();

            $this->recordActivity->handle(
                $card, $actor, ActivityAction::Rejected,
                ['status' => $before->value], ['status' => $card->status->value], $reason,
            );
        });

        $card->notifyAuthor(new CardRejected($card));

        return $card;
    }
}
