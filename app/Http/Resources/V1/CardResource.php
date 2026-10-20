<?php

namespace App\Http\Resources\V1;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une carte. L'adresse e-mail d'un auteur sans compte n'est jamais exposée.
 *
 * @mixin Card
 */
class CardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum');
        $affiliation = $this->resource->relationLoaded('publicFigure') && $this->publicFigure->relationLoaded('affiliations')
            ? $this->publicFigure->affiliationOn($this->stated_on)
            : null;

        return [
            'id' => $this->id,
            'type' => EnumValue::of($this->type),
            'status' => EnumValue::of($this->status),
            'quote' => $this->quote,
            'context' => $this->context,
            'stated_on' => $this->stated_on->toDateString(),
            'testable_statement' => $this->testable_statement,
            'verdict_criterion' => $this->verdict_criterion,
            'deadline' => $this->deadline?->toDateString(),
            'deadline_nature' => EnumValue::of($this->deadline_nature),
            'version' => $this->version,
            'published_at' => $this->published_at?->toIso8601String(),
            'public_figure' => PublicFigureResource::make($this->whenLoaded('publicFigure')),
            /** Affiliation de la personnalité au moment de la déclaration. */
            'affiliation_at_statement' => $affiliation ? AffiliationResource::make($affiliation) : null,
            'themes' => ThemeResource::collection($this->whenLoaded('themes')),
            /** Pseudo du membre auteur ; null pour un visiteur sans compte. */
            'author' => $this->whenLoaded('author', fn () => $this->author?->username),
            'is_own' => $viewer !== null && $this->resource->isAuthoredBy($viewer),
            'verdict' => VerdictResource::make($this->whenLoaded('approvedVerdict')),
            'sources' => SourceResource::collection($this->whenLoaded('sources')),
            'history' => ActivityLogResource::collection($this->whenLoaded('activityLogs')),
            'rejection_reason' => $this->when($this->rejection_reason !== null, $this->rejection_reason),
            'url' => route('api.v1.cards.show', $this->resource),
        ];
    }
}
