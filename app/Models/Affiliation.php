<?php

namespace App\Models;

use Database\Factories\AffiliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['organization', 'role', 'started_on', 'ended_on'])]
class Affiliation extends Model
{
    /** @use HasFactory<AffiliationFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /** @return BelongsTo<PublicFigure, $this> */
    public function publicFigure(): BelongsTo
    {
        return $this->belongsTo(PublicFigure::class);
    }

    public function coversDate(Carbon $date): bool
    {
        return ($this->started_on === null || $this->started_on->lte($date))
            && ($this->ended_on === null || $this->ended_on->gte($date));
    }
}
