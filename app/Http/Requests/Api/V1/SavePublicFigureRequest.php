<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Category;
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
            /** Slug d'une catégorie existante (politique, journaliste, editorialiste, economiste, autre…). */
            'category' => [$required, 'string', Rule::exists('categories', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('public_figures', 'slug')->ignore($figure)],
            'wikidata_id' => ['nullable', 'regex:/^Q\d+$/', Rule::unique('public_figures', 'wikidata_id')->ignore($figure)],
            /** Lien direct vers le fichier image (.jpg, .png, .webp…), pas vers une page web. */
            'photo_url' => ['nullable', 'url:http,https', 'max:2000', 'regex:'.PublicFigure::PHOTO_URL_PATTERN],
            /** Remplace toute la liste : renvoyer l'« id » des affiliations à conserver. */
            'affiliations' => ['sometimes', 'array'],
            'affiliations.*.id' => ['nullable', 'uuid'],
            'affiliations.*.organization' => ['required', 'string', 'max:255'],
            'affiliations.*.role' => ['nullable', 'string', 'max:255'],
            'affiliations.*.started_on' => ['nullable', 'date'],
            'affiliations.*.ended_on' => ['nullable', 'date', 'after_or_equal:affiliations.*.started_on'],
        ];
    }

    /**
     * Données pour SavePublicFigure : la catégorie passe de son slug à son identifiant.
     *
     * @return array<string, mixed>
     */
    public function toActionData(): array
    {
        $data = $this->safe()->except('category');

        if ($this->has('category')) {
            $data['category_id'] = Category::where('slug', $this->validated('category'))->value('id');
        }

        return $data;
    }
}
