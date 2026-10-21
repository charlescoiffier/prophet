<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\SourceKind;
use Illuminate\Validation\Rule;

/**
 * Règles de forme partagées par la proposition et la modification d'une carte.
 */
class CardRules
{
    /**
     * @return array<string, mixed>
     */
    public static function card(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'public_figure_id' => [$partial ? 'sometimes' : 'required_without:requested_figure', 'nullable', 'uuid', Rule::exists('public_figures', 'id')],
            /** Personnalité absente de la liste : son nom et sa fonction, pour qu'un modérateur crée sa fiche. */
            'requested_figure' => ['nullable', 'string', 'max:255'],
            /** Thèmes absents de la liste, séparés par des virgules. */
            'requested_themes' => ['nullable', 'string', 'max:500'],
            'type' => [$required, Rule::enum(CardType::class)],
            'quote' => [$required, 'string', 'max:2000'],
            'context' => ['nullable', 'string', 'max:5000'],
            'stated_on' => [$required, 'date', 'before_or_equal:today'],
            'testable_statement' => ['nullable', 'string', 'max:2000'],
            'verdict_criterion' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['nullable', 'date'],
            'deadline_nature' => ['nullable', 'required_with:deadline', Rule::enum(DeadlineNature::class)],
            'themes' => ['sometimes', 'array', 'max:5'],
            'themes.*' => ['uuid', Rule::exists('themes', 'id')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function sources(): array
    {
        return [
            'sources' => ['required', 'array', 'min:1', 'max:10'],
            'sources.*.url' => ['required', 'url:http,https', 'max:2000'],
            'sources.*.kind' => ['required', Rule::enum(SourceKind::class)],
            'sources.*.outlet' => ['nullable', 'string', 'max:255'],
            'sources.*.published_on' => ['nullable', 'date'],
            'sources.*.timecode' => ['nullable', 'regex:/^\d{1,2}(:\d{2}){1,2}$/'],
        ];
    }
}
