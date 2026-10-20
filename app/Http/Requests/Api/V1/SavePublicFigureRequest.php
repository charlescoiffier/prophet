<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PublicFigureCategory;
use App\Models\PublicFigure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePublicFigureRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PublicFigure|null $figure */
        $figure = $this->route('publicFigure');
        $required = $figure === null ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'category' => [$required, Rule::enum(PublicFigureCategory::class)],
            'description' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('public_figures', 'slug')->ignore($figure)],
            'wikidata_id' => ['nullable', 'regex:/^Q\d+$/', Rule::unique('public_figures', 'wikidata_id')->ignore($figure)],
            'photo_url' => ['nullable', 'url:http,https', 'max:2000'],
            /** Remplace toute la liste : renvoyer l'« id » des affiliations à conserver. */
            'affiliations' => ['sometimes', 'array'],
            'affiliations.*.id' => ['nullable', 'uuid'],
            'affiliations.*.organization' => ['required', 'string', 'max:255'],
            'affiliations.*.role' => ['nullable', 'string', 'max:255'],
            'affiliations.*.started_on' => ['nullable', 'date'],
            'affiliations.*.ended_on' => ['nullable', 'date', 'after_or_equal:affiliations.*.started_on'],
        ];
    }
}
