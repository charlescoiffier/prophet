<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\Altcha;
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
            /** Sans jeton : solution du défi anti-robot obtenu par GET /altcha (base64). */
            'altcha' => $this->user('sanctum') === null ? ['required', 'string', new Altcha] : ['prohibited'],
        ];
    }
}
