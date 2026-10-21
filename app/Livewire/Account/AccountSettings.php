<?php

namespace App\Livewire\Account;

use App\Actions\Users\UpdateAccount;
use App\Actions\Users\UpdatePassword;
use App\Http\Requests\Api\V1\AccountRules;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Mon compte : prénom, nom, pseudo, adresse et mot de passe du membre connecté.
 */
#[Title('Mon compte')]
class AccountSettings extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $username = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = $this->user();

        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->username = $user->username;
        $this->email = $user->email;
    }

    public function saveProfile(UpdateAccount $updateAccount): void
    {
        $user = $this->user();
        $previousEmail = $user->email;

        $data = $this->validate(AccountRules::profile($user));

        $updateAccount->handle($user, $user, $data);

        $this->email = $user->email;

        session()->flash('profile-status', $user->email !== $previousEmail
            ? 'C’est enregistré. Un lien de confirmation vient de partir vers votre nouvelle adresse.'
            : 'C’est enregistré.');
    }

    /**
     * Cinq essais du mot de passe actuel par minute, comme à la connexion.
     */
    public function savePassword(UpdatePassword $updatePassword): void
    {
        $key = 'account-password:'.$this->user()->getKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'current_password' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);

        $data = $this->validate(AccountRules::password());

        RateLimiter::clear($key);

        $updatePassword->handle($this->user(), $data['password']);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('password-status', 'Votre mot de passe a été changé.');
    }

    public function resendVerification(): void
    {
        $this->user()->sendEmailVerificationNotification();

        session()->flash('profile-status', 'Un nouveau lien de confirmation vient de vous être envoyé.');
    }

    public function render(): View
    {
        return view('livewire.account.account-settings', ['user' => $this->user()]);
    }

    private function user(): User
    {
        return auth()->user();
    }
}
