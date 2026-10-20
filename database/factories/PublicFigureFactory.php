<?php

namespace Database\Factories;

use App\Enums\PublicFigureCategory;
use App\Models\PublicFigure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PublicFigure>
 */
class PublicFigureFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(PublicFigureCategory::cases()),
            'description' => fake()->jobTitle(),
        ];
    }
}
