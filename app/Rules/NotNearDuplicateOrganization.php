<?php

namespace App\Rules;

use App\Support\Organizations;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse un nom d'organisation très proche d'une organisation connue (faute de frappe probable),
 * sauf si l'on confirme qu'il s'agit bien d'une autre organisation. Une organisation déjà connue passe toujours.
 */
class NotNearDuplicateOrganization implements ValidationRule
{
    public function __construct(private bool $confirmedDistinct = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->confirmedDistinct || ! is_string($value) || Organizations::isKnown($value)) {
            return;
        }

        $near = Organizations::nearDuplicates($value);

        if ($near !== []) {
            $fail(__('validation.custom.organization.near_duplicate', [
                'names' => implode(' », « ', $near),
            ]));
        }
    }
}
