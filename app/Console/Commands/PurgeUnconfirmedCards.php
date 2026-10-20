<?php

namespace App\Console\Commands;

use App\Enums\CardStatus;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Notifications\ConfirmCardEmail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * RGPD : une carte dont l'e-mail n'a jamais été confirmé n'a jamais été publique ;
 * passé le délai du lien, on l'efface avec l'adresse du visiteur.
 */
#[Signature('cards:purge-unconfirmed')]
#[Description('Efface les cartes dont l’adresse e-mail n’a pas été confirmée à temps')]
class PurgeUnconfirmedCards extends Command
{
    public function handle(): int
    {
        $ids = Card::where('status', CardStatus::AwaitingEmail)
            ->where('created_at', '<', now()->subDays(ConfirmCardEmail::VALID_DAYS)->subDay())
            ->pluck('id');

        // Sources et thèmes partent en cascade ; le journal (polymorphe) est effacé à la main.
        $count = DB::transaction(function () use ($ids) {
            ActivityLog::where('subject_type', (new Card)->getMorphClass())->whereIn('subject_id', $ids)->delete();

            return Card::whereKey($ids)->delete();
        });

        $this->info("{$count} carte(s) non confirmée(s) effacée(s).");

        return self::SUCCESS;
    }
}
