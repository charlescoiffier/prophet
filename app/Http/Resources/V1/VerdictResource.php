<?php

namespace App\Http\Resources\V1;

use App\Models\Verdict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Verdict
 */
class VerdictResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'value' => EnumValue::of($this->value),
            'status' => EnumValue::of($this->status),
            'justification' => $this->justification,
            'proposed_by' => $this->whenLoaded('proposer', fn () => $this->proposer->username),
            'decided_by' => $this->whenLoaded('decider', fn () => $this->decider?->username),
            'proposed_at' => $this->created_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'sources' => SourceResource::collection($this->whenLoaded('sources')),
        ];
    }
}
