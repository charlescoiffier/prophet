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
 * deux fois à l'invite, sans écho : il n'apparaît ni dans l'historique du terminal ni dans
 * les journaux. --password redéfinit celui d'un compte existant.
 */
#[Signature('users:create-admin {email} {username} {--password : Redéfinir le mot de passe d’un compte existant}')]
#[Description('Crée le compte administrateur (mot de passe demandé à l’invite)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower($this->argument('email'));
        $user = User::where('email', $email)->first();

        if ($user === null || $this->option('password')) {
            $password = $this->askPassword();

            if ($password === null) {
                return self::FAILURE;
            }
        }

        if ($user === null) {
            $validator = Validator::make(
                ['email' => $email, 'username' => $this->argument('username')],
                [
                    'email' => ['required', 'email', 'unique:users,email'],
                    'username' => ['required', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
                ],
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            $user = User::create(['username' => $this->argument('username'), 'email' => $email, 'password' => $password]);
        } elseif (isset($password)) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
            $this->info('Mot de passe redéfini.');
        }

        // Rôle et confirmation de l'adresse : jamais « fillable », posés explicitement.
        $user->forceFill(['role' => Role::Admin, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info("{$user->username} ({$user->email}) est administrateur.");

        return self::SUCCESS;
    }

    private function askPassword(): ?string
    {
        $password = (string) $this->secret('Mot de passe (10 caractères au moins)');

        if ((string) $this->secret('Confirmer le mot de passe') !== $password) {
            $this->error('Les deux saisies ne correspondent pas.');

            return null;
        }

        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::defaults()]]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return null;
        }

        return $password;
    }
}
