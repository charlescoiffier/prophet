<?php

namespace App\Console\Commands;

use App\Models\PublicFigure;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ThemeSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Charge les données de démonstration fictives sur le site de test, comptes verrouillés :
 * aucun compte de démonstration ne peut être ouvert. À effacer avant le lancement (demo:purge).
 */
#[Signature('demo:load')]
#[Description('Charge les données de démonstration fictives (comptes verrouillés)')]
class LoadDemo extends Command
{
    public function handle(): int
    {
        if (PublicFigure::whereIn('slug', array_map(fn (string $name) => Str::slug($name), DemoSeeder::FIGURES))->exists()) {
            $this->error('Des données de démonstration sont déjà chargées (demo:purge pour les effacer).');

            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->callSilently('db:seed', ['--class' => ThemeSeeder::class, '--force' => true]);
            (new DemoSeeder)->setContainer($this->laravel)->locked()->run();
        });

        $this->info('Données de démonstration chargées. Aucun de leurs comptes ne peut être ouvert.');

        return self::SUCCESS;
    }
}
