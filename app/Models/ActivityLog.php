<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\PublicFigureCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Journal des actions de modération. Une ligne n'est jamais modifiée après écriture.
 */
#[Fillable(['action', 'before', 'after', 'comment'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Changements lisibles : « champ : avant → après », un par ligne.
     */
    public function describeChanges(): string
    {
        $keys = array_unique([...array_keys($this->before ?? []), ...array_keys($this->after ?? [])]);

        return collect($keys)
            ->map(function (string $key) {
                $label = __("validation.attributes.{$key}");
                $label = $label === "validation.attributes.{$key}" ? $key : $label;

                return $label.' : '.$this->format($key, $this->before[$key] ?? null).' → '.$this->format($key, $this->after[$key] ?? null);
            })
            ->implode("\n");
    }

    /**
     * Valeurs d'enum stockées en anglais, affichées avec leur libellé français.
     *
     * @var array<string, class-string<\BackedEnum>>
     */
    private const ENUM_FIELDS = [
        'type' => CardType::class,
        'deadline_nature' => DeadlineNature::class,
        'value' => VerdictValue::class,
        'verdict' => VerdictValue::class,
        'role' => Role::class,
        'category' => PublicFigureCategory::class,
    ];

    private function format(string $key, mixed $value): string
    {
        $enum = $key === 'status' ? $this->statusEnum() : (self::ENUM_FIELDS[$key] ?? null);

        return match (true) {
            $value === null, $value === [] => '∅',
            is_array($value) => implode(', ', $value),
            $enum !== null && $enum::tryFrom($value) !== null => $enum::from($value)->label(),
            default => (string) $value,
        };
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    private function statusEnum(): ?string
    {
        return match ($this->subject_type) {
            (new Card)->getMorphClass() => CardStatus::class,
            (new Verdict)->getMorphClass() => VerdictStatus::class,
            (new Report)->getMorphClass() => ReportStatus::class,
            default => null,
        };
    }
}
