<?php

namespace App\Models;

use App\Enums\PublicFigureCategory;
use Database\Factories\PublicFigureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'slug', 'category', 'description', 'photo_url', 'wikidata_id'])]
class PublicFigure extends Model
{
    /** @use HasFactory<PublicFigureFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'category' => PublicFigureCategory::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Card, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /** @return HasMany<Affiliation, $this> */
    public function affiliations(): HasMany
    {
        return $this->hasMany(Affiliation::class)->orderByDesc('started_on');
    }

    /**
     * Affiliation en cours à une date donnée (celle d'une déclaration, par exemple).
     */
    public function affiliationOn(Carbon $date): ?Affiliation
    {
        return $this->affiliations
            ->first(fn (Affiliation $affiliation) => $affiliation->coversDate($date));
    }
}
