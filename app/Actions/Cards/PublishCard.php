<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\CardPublished;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PublishCard
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Card $card, User $actor): Card
    {
        Gate::forUser($actor)->authorize('publish', $card);

        // Seul l'admin peut publier sa propre carte (CardPolicy) : l'historique public le dit.
        $selfPublished = $card->isAuthoredBy($actor);

        DB::transaction(function () use ($card, $actor, $selfPublished) {
            $before = $card->status;

            $card->status = CardStatus::Published;
            $card->published_at = now();
            $card->save();

            $this->recordActivity->handle(
                $card, $actor, ActivityAction::Approved,
                ['status' => $before->value], ['status' => $card->status->value],
                $selfPublished ? __('moderation.self_published') : null,
            );
        });

        if (! $selfPublished) {
            $card->notifyAuthor(new CardPublished($card));
        }

        return $card;
    }
}
