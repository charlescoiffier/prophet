<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crée le compte administrateur, ou promeut un compte existant. Le mot de passe est saisi
 * à l'invite, sans écho, et n'apparaît ni dans l'historique du terminal ni dans les journaux.
 */
#[Signature('users:create-admin {email} {username}')]
#[Description('Crée le compte administrateur (mot de passe demandé à l’invite)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower($this->argument('email'));
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $password = (string) $this->secret('Mot de passe');

            $validator = Validator::make(
                ['email' => $email, 'username' => $this->argument('username'), 'password' => $password],
                [
                    'email' => ['required', 'email', 'unique:users,email'],
                    'username' => ['required', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
                    'password' => ['required', Password::defaults()],
                ],
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            $user = User::create(['username' => $this->argument('username'), 'email' => $email, 'password' => $password]);
        }

        // Rôle et confirmation de l'adresse : jamais « fillable », posés explicitement.
        $user->forceFill(['role' => Role::Admin, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info("{$user->username} est administrateur.");

        return self::SUCCESS;
    }
}
