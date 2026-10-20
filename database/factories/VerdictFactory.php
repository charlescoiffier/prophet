<?php

namespace Database\Factories;

use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verdict>
 */
class VerdictFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory()->overdue(),
            // La valeur est choisie selon le type de la carte.
            'value' => fn (array $attributes) => fake()->randomElement(
                VerdictValue::forType(Card::findOrFail($attributes['card_id'])->type)
            ),
            'justification' => fake()->paragraph(),
            'proposed_by' => User::factory(),
            'status' => VerdictStatus::Proposed,
        ];
    }

    public function approved(?User $moderator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VerdictStatus::Approved,
            'decided_by' => $moderator ?? User::factory()->moderator(),
            'decided_at' => now(),
        ]);
    }
}
