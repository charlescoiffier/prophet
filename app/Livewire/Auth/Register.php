<?php

namespace App\Livewire\Auth;

use App\Actions\Users\RegisterUser;
use App\Livewire\Concerns\VerifiesAltcha;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::dialog')]
#[Title('Créer un compte')]
class Register extends Component
{
    use VerifiesAltcha;

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(RegisterUser $registerUser): void
    {
        $data = $this->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users', 'username')],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->verifyAltcha();

        $user = $registerUser->handle($data);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('account.cards', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
