<?php

namespace Database\Factories;

use App\Models\Category;
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
            // Une des catégories d'origine, créées par la migration.
            'category_id' => fn () => Category::inRandomOrder()->value('id') ?? Category::factory(),
        ];
    }
}
