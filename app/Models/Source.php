<?php

namespace App\Models;

use App\Enums\SourceKind;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une source étaye soit une carte, soit un verdict (contrainte en base).
 */
#[Fillable(['url', 'kind', 'outlet', 'published_on', 'timecode'])]
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'kind' => SourceKind::class,
            'published_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Card, $this> */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /** @return BelongsTo<Verdict, $this> */
    public function verdict(): BelongsTo
    {
        return $this->belongsTo(Verdict::class);
    }

    public function isArchived(): bool
    {
        return $this->archive_url !== null;
    }
}
