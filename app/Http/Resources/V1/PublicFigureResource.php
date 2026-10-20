<?php

namespace App\Http\Resources\V1;

use App\Models\PublicFigure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PublicFigure
 */
class PublicFigureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            /** Catégorie : slug stable et nom affiché. */
            'category' => $this->whenLoaded('category', fn () => ['value' => $this->category->slug, 'label' => $this->category->name]),
            'description' => $this->description,
            'photo_url' => $this->resource->photoUrl(),
            'wikidata_id' => $this->wikidata_id,
            'affiliations' => AffiliationResource::collection($this->whenLoaded('affiliations')),
            'cards_count' => $this->whenCounted('cards'),
        ];
    }
}
