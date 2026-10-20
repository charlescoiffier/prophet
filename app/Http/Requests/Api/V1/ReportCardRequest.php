<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReportCardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            /** Obligatoire sans compte, pour pouvoir vous répondre. Jamais publiée. */
            'email' => [$this->user('sanctum') === null ? 'required' : 'prohibited', 'email:rfc', 'max:255'],
        ];
    }
}
