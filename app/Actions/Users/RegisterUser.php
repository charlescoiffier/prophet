<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

/**
 * Inscription d'un membre. L'e-mail de confirmation part sur l'événement Registered ;
 * les cartes proposées avec cette adresse seront rattachées à la confirmation (règle 8).
 */
class RegisterUser
{
    /**
     * @param  array{username: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        return $user;
    }
}
