<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\VerdictValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerdictRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['sometimes', Rule::enum(VerdictValue::class)],
            'justification' => ['sometimes', 'string', 'max:5000'],
        ];
    }
}
