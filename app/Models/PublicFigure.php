<?php

namespace App\Models;

use App\Enums\CardType;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Support\Names;
use Database\Factories\PublicFigureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable(['first_name', 'last_name', 'name', 'slug', 'category_id', 'description', 'photo_url', 'photo_path', 'photo_original_path', 'photo_settings', 'wikidata_id'])]
class PublicFigure extends Model
{
    /** @use HasFactory<PublicFigureFactory> */
    use HasFactory, HasUuids;

    /**
     * Un lien de photo doit viser le fichier image lui-même, pas une page web : l'extension doit
     * terminer le chemin (avant d'éventuels paramètres), et il n'y a pas de fragment « # ».
     */
    public const PHOTO_URL_PATTERN = '/^[^#?]+\.(jpe?g|png|webp|gif|avif|svg)(\?[^#]*)?$/i';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photo_settings' => 'array',
        ];
    }

    /**
     * « name » est le nom affiché, recomposé à partir du prénom et du nom quand ils changent ;
     * un nom complet donné seul (données anciennes, tests) est coupé en prénom et nom.
     */
    protected static function booted(): void
    {
        static::saving(function (PublicFigure $figure) {
            if ($figure->isDirty(['first_name', 'last_name']) && filled($figure->last_name)) {
                $figure->first_name = filled($figure->first_name) ? trim($figure->first_name) : null;
                $figure->last_name = trim($figure->last_name);
                $figure->name = Names::full($figure->first_name, $figure->last_name);
            } elseif ($figure->isDirty('name') && filled($figure->name)) {
                [$figure->first_name, $figure->last_name] = Names::split($figure->name);
            }
        });
    }

    /**
     * Ordre alphabétique par nom de famille, puis prénom, sans tenir compte des accents ni de la casse.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function alphabetical(Builder $query): void
    {
        $query->orderByRaw('unaccent(lower(last_name)), unaccent(lower(coalesce(first_name, \'\')))');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Photo affichée : celle téléversée en modération, sinon le lien externe.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path !== null
            ? Storage::disk('public')->url($this->photo_path)
            : $this->photo_url;
    }

    /**
     * Photo passée par l'atelier portrait : déjà en deux tons, sur fond transparent.
     */
    public function hasProcessedPhoto(): bool
    {
        return $this->photo_path !== null && $this->photo_settings !== null;
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
