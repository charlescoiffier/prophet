<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Vocabulaire fermé des thèmes. Peut tourner en production.
 */
class ThemeSeeder extends Seeder
{
    public const THEMES = [
        'Présidentielle 2027',
        'Économie',
        'Emploi',
        'Famille',
        'Petite enfance',
        'Institutions',
        'Santé',
        'Sécurité',
        'Éducation',
        'Culture',
        'Environnement',
    ];

    public function run(): void
    {
        foreach (self::THEMES as $name) {
            Theme::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
