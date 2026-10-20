<?php

namespace App\Http\Controllers;

use App\Actions\Users\VerifyEmail;
use App\Models\User;
use Illuminate\Contracts\View\View;

class VerifyEmailController extends Controller
{
    /**
     * Paramètres « id » et « hash » : noms imposés par la notification VerifyEmail de Laravel.
     */
    public function __invoke(string $id, string $hash, VerifyEmail $verifyEmail): View
    {
        $user = User::findOrFail($id);

        abort_unless($verifyEmail->handle($user, $hash), 403);

        return view('auth.email-verified', ['user' => $user]);
    }
}
