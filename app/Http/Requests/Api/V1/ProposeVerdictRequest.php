<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\VerdictValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposeVerdictRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Promesse : kept, partially_kept, compromised, abandoned, moot. Prédiction : correct, partially_correct, wrong, unverifiable. */
            'value' => ['required', Rule::enum(VerdictValue::class)],
            'justification' => ['required', 'string', 'max:5000'],
            ...CardRules::sources(),
        ];
    }
}
