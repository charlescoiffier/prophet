<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Données de démonstration, entièrement fictives (reprises des maquettes).
     * Ne jamais y mettre de vraies citations ni de vraies personnalités.
     */
    public function run(): void
    {
        // En production, seulement le vocabulaire des thèmes : jamais les données fictives.
        $this->call(app()->isProduction()
            ? [ThemeSeeder::class]
            : [ThemeSeeder::class, DemoSeeder::class]);
    }
}
