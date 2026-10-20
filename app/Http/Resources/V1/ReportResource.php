<?php

namespace App\Http\Resources\V1;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Signalement, visible des seuls modérateurs (le texte peut contenir des données personnelles).
 *
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'reason' => $this->reason,
            'status' => EnumValue::of($this->status),
            'reported_by' => $this->whenLoaded('user', fn () => $this->user?->username),
            'reported_at' => $this->created_at->toIso8601String(),
        ];
    }
}
