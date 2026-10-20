<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Proposition d'une carte. Sans jeton, l'adresse e-mail est obligatoire (règle 1).
 */
class ProposeCardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...CardRules::card(),
            ...CardRules::sources(),
            /** Obligatoire sans compte : un lien de confirmation y est envoyé. */
            'email' => [$this->user('sanctum') === null ? 'required' : 'prohibited', 'email:rfc', 'max:255'],
        ];
    }
}
