<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\User;
use App\Models\Verdict;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Efface les données de démonstration avant le lancement : personnalités fictives et leurs cartes
 * (sources, verdicts, signalements, historique), thèmes de démonstration sans carte, comptes en @example.test.
 * Le reste (vraies cartes, comptes, vocabulaire des thèmes) n'est pas touché.
 */
#[Signature('demo:purge {--force : Ne pas demander de confirmation}')]
#[Description('Efface les données de démonstration fictives')]
class PurgeDemo extends Command
{
    public function handle(): int
    {
        $figures = PublicFigure::whereIn('slug', array_map(fn (string $name) => Str::slug($name), DemoSeeder::FIGURES));
        $cards = Card::whereIn('public_figure_id', (clone $figures)->select('id'));
        $users = User::where('email', 'like', '%@'.DemoSeeder::EMAIL_DOMAIN);

        $this->line(sprintf('%d personnalités, %d cartes, %d comptes de démonstration.',
            (clone $figures)->count(), (clone $cards)->count(), (clone $users)->count()));

        if (! $this->option('force') && ! $this->confirm('Les effacer définitivement ?')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($figures, $cards, $users) {
            $cardIds = (clone $cards)->pluck('id');
            $verdictIds = Verdict::whereIn('card_id', $cardIds)->pluck('id');

            // Le journal est polymorphe (pas de clé étrangère) : on l'efface à la main.
            ActivityLog::where(fn ($query) => $query
                ->where(fn ($card) => $card->where('subject_type', (new Card)->getMorphClass())->whereIn('subject_id', $cardIds))
                ->orWhere(fn ($verdict) => $verdict->where('subject_type', (new Verdict)->getMorphClass())->whereIn('subject_id', $verdictIds))
                ->orWhere(fn ($figure) => $figure->where('subject_type', (new PublicFigure)->getMorphClass())->whereIn('subject_id', (clone $figures)->select('id')))
            )->delete();

            // Sources, verdicts, signalements et thèmes des cartes partent en cascade.
            Card::whereKey($cardIds)->delete();
            (clone $figures)->delete();
            Theme::whereIn('name', DemoSeeder::THEMES)->doesntHave('cards')->delete();

            ActivityLog::where('subject_type', (new User)->getMorphClass())->whereIn('subject_id', (clone $users)->select('id'))->delete();
            (clone $users)->delete();
        });

        $this->info('Données de démonstration effacées.');

        return self::SUCCESS;
    }
}
