<?php

namespace App\Models;

use App\Enums\CardType;
use App\Enums\PublicFigureCategory;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use Database\Factories\PublicFigureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('created_at');
    }

    /**
     * Bilan des cartes tranchées : nombre de verdicts validés, par type de carte et par valeur.
     * Des décomptes, pas des pourcentages (pas d'agrégat qui ressemble à un sondage).
     *
     * @return array<string, array<string, int>>
     */
    public function record(): array
    {
        $counts = Verdict::query()
            ->join('cards', 'cards.id', '=', 'verdicts.card_id')
            ->where('cards.public_figure_id', $this->getKey())
            ->where('verdicts.status', VerdictStatus::Approved)
            ->groupBy('cards.type', 'verdicts.value')
            ->selectRaw('cards.type as card_type, verdicts.value as verdict_value, count(*) as total')
            ->get();

        return collect(CardType::cases())->mapWithKeys(fn (CardType $type) => [
            $type->value => collect(VerdictValue::forType($type))->mapWithKeys(fn (VerdictValue $value) => [
                $value->value => (int) $counts
                    ->first(fn ($row) => $row->card_type === $type->value && $row->verdict_value === $value->value)
                    ?->total,
            ])->all(),
        ])->all();
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
