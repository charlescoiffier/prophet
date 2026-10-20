<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            /** Nom de l'appareil ou du client, pour retrouver le jeton. */
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
