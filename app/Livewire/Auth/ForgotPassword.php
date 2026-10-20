<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::dialog')]
#[Title('Mot de passe oublié')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        if (RateLimiter::attempt('forgot:'.request()->ip(), 5, fn () => true, 600)) {
            Password::sendResetLink(['email' => mb_strtolower($this->email)]);
        }

        // Même réponse que l'adresse existe ou non.
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
