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
        $this->call([
            ThemeSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
