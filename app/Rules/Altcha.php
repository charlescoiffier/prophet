<?php

namespace App\Rules;

use App\Support\AltchaGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Vérification anti-robot ALTCHA, exigée des visiteurs sans compte.
 */
class Altcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(AltchaGuard::class)->verify($value)) {
            $fail('validation.custom.altcha.invalid')->translate();
        }
    }
}
