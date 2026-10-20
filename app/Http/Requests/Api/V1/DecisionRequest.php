<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Décision de modération : « approve » (publier ou valider) ou « reject », avec motif.
 */
class DecisionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            /** Obligatoire pour un refus : envoyé à l'auteur et inscrit à l'historique public. */
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function approves(): bool
    {
        return $this->validated('decision') === 'approve';
    }
}
