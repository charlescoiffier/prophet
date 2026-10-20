<?php

namespace Database\Factories;

use App\Enums\SourceKind;
use App\Models\Card;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        $kind = fake()->randomElement(SourceKind::cases());

        return [
            'card_id' => Card::factory(),
            'verdict_id' => null,
            'url' => 'https://example.org/'.fake()->slug(),
            'kind' => $kind,
            'outlet' => fake()->company(),
            'published_on' => fake()->dateTimeBetween('-3 years'),
            'timecode' => in_array($kind, [SourceKind::Video, SourceKind::Audio], true)
                ? sprintf('%02d:%02d', fake()->numberBetween(0, 59), fake()->numberBetween(0, 59))
                : null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archive_url' => 'https://web.archive.org/web/2026/'.$attributes['url'],
            'archived_at' => now(),
        ]);
    }
}
