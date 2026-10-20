<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ResetPassword;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

#[Group('Comptes', weight: 4)]
class PasswordController extends Controller
{
    /**
     * Mot de passe oublié
     *
     * Envoie un lien de réinitialisation. La réponse est la même que l'adresse existe ou non.
     *
     * @unauthenticated
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => __('api.password_link_sent')], 202);
    }

    /**
     * Réinitialiser le mot de passe
     *
     * Avec le jeton reçu par e-mail. Révoque tous les jetons d'API du compte.
     *
     * @unauthenticated
     */
    public function reset(Request $request, ResetPassword $resetPassword): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $resetPassword->handle($request->input('email'), $request->input('token'), $request->input('password'));

        return response()->json(['message' => __(Password::PASSWORD_RESET)]);
    }
}
