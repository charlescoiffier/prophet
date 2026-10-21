<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Règles de forme d'un compte, partagées par l'inscription, « Mon compte » (site et API) et la modération.
 */
class AccountRules
{
    /**
     * Pseudo et adresse uniques, comptes supprimés compris (ils peuvent être restaurés).
     *
     * @return array<string, mixed>
     */
    public static function profile(?User $user = null): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user)],
        ];
    }

    /**
     * @param  string  $guard  garde de la personne connectée : « web » sur le site, « sanctum » dans l'API
     * @return array<string, mixed>
     */
    public static function password(string $guard = 'web'): array
    {
        return [
            'current_password' => ['required', 'current_password:'.$guard],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
