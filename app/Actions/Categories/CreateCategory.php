<?php

namespace App\Actions\Categories;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\Category;
use App\Models\User;
use App\Support\Names;
use Illuminate\Support\Facades\Gate;

/**
 * Ajoute une catégorie de personnalité, ou renvoie celle qui existe déjà sous une autre graphie.
 */
class CreateCategory
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $actor, string $name): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        $name = trim($name);
        $existing = Category::all()->first(fn (Category $category) => Names::normalize($category->name) === Names::normalize($name));

        if ($existing !== null) {
            return $existing;
        }

        $category = Category::create(['name' => $name]);
        $this->recordActivity->handle($category, $actor, ActivityAction::CategoryCreated, after: ['name' => $name]);

        return $category;
    }
}
