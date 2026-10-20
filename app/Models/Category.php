<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Catégorie d'une personnalité (politique, journaliste…), enrichissable par les modérateurs.
 */
#[Fillable(['name', 'slug'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->slug = filled($category->slug) ? $category->slug : Str::slug($category->name);
        });
    }

    /** @return HasMany<PublicFigure, $this> */
    public function publicFigures(): HasMany
    {
        return $this->hasMany(PublicFigure::class);
    }
}
