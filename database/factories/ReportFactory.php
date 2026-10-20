<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Card;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory()->published(),
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'reason' => fake()->sentence(),
            'status' => ReportStatus::Open,
        ];
    }
}
