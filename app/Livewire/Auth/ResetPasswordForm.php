<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\ResetPassword;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Page « /mot-de-passe/nouveau » vers laquelle pointe l'e-mail de réinitialisation.
 */
#[Layout('layouts::dialog')]
#[Title('Nouveau mot de passe')]
class ResetPasswordForm extends Component
{
    #[Url]
    public string $token = '';

    #[Url]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(ResetPassword $resetPassword): void
    {
        $this->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $resetPassword->handle($this->email, $this->token, $this->password);

        session()->flash('status', __('passwords.reset'));
        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password-form');
    }
}
