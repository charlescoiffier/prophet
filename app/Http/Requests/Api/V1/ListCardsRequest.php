<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Cards\SearchCards;
use App\Enums\CardStatus;
use App\Enums\CardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCardsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sorts = collect(SearchCards::SORTS)->flatMap(fn (string $sort) => [$sort, "-{$sort}"])->all();

        return [
            /** Filtre : chaque mot doit figurer dans la citation (sans accents ni majuscules). */
            'q' => ['nullable', 'string', 'max:200'],
            /** Slug de la personnalité. */
            'public_figure' => ['nullable', 'string', 'max:255'],
            /** Slug du thème. */
            'theme' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(CardType::class)],
            'status' => ['nullable', Rule::in(array_map(fn (CardStatus $status) => $status->value, CardStatus::public()))],
            'deadline_before' => ['nullable', 'date'],
            'deadline_after' => ['nullable', 'date'],
            /** published_at, deadline ou stated_on ; « - » en tête pour l'ordre décroissant. */
            'sort' => ['nullable', Rule::in($sorts)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
