<?php

namespace App\Http\Resources\V1;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une ligne de l'historique public (règle 6).
 *
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'at' => $this->created_at->toIso8601String(),
            'action' => EnumValue::of($this->action),
            /** Pseudo du modérateur ou du membre ; null pour un visiteur ou le système. */
            'actor' => $this->actor?->username,
            'before' => $this->before,
            'after' => $this->after,
            'summary' => $this->describeChanges(),
            'comment' => $this->comment,
        ];
    }
}
