<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Le visiteur a cliqué sur le lien signé : la carte part en modération (règle 1).
 * Si un compte confirmé existe déjà avec cette adresse, la carte lui est rattachée.
 */
class ConfirmCardEmail
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Card $card): Card
    {
        // Un second clic sur le lien ne change rien.
        if ($card->status !== CardStatus::AwaitingEmail) {
            return $card;
        }

        DB::transaction(function () use ($card) {
            $card->status = CardStatus::InModeration;
            $card->email_confirmed_at = now();

            $account = User::whereRaw('lower(email) = ?', [mb_strtolower($card->author_email)])
                ->whereNotNull('email_verified_at')
                ->first();

            if ($account !== null) {
                $card->author()->associate($account);
            }

            $card->save();

            $this->recordActivity->handle(
                $card, null, ActivityAction::EmailConfirmed,
                ['status' => CardStatus::AwaitingEmail->value], ['status' => CardStatus::InModeration->value],
            );
        });

        return $card;
    }
}
