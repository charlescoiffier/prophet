<?php

namespace App\Http\Resources\V1;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Source
 */
class SourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'kind' => EnumValue::of($this->kind),
            'outlet' => $this->outlet,
            'published_on' => $this->published_on?->toDateString(),
            'timecode' => $this->timecode,
            'archive_url' => $this->archive_url,
            'archived_at' => $this->archived_at?->toIso8601String(),
        ];
    }
}
