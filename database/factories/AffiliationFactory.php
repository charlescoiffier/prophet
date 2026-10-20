<?php

namespace Database\Factories;

use App\Models\Affiliation;
use App\Models\PublicFigure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affiliation>
 */
class AffiliationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_figure_id' => PublicFigure::factory(),
            'organization' => fake()->company(),
            'role' => fake()->randomElement(['Candidat', 'Député', 'Ministre', 'Chroniqueur', 'Porte-parole']),
            'started_on' => fake()->dateTimeBetween('-10 years', '-1 year'),
            'ended_on' => null,
        ];
    }
}
