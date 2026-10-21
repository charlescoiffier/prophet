<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\RegisterUser;
use App\Actions\Users\UpdateAccount;
use App\Actions\Users\UpdatePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Resources\V1\AccountResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Comptes', weight: 4)]
class AccountController extends Controller
{
    /**
     * Créer un compte
     *
     * Un lien de confirmation part par e-mail. Une fois l'adresse confirmée, les cartes proposées
     * avec elle sont rattachées au compte (règle 8).
     *
     * @unauthenticated
     */
    public function store(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->handle($request->validated());

        return AccountResource::make($user)->response()->setStatusCode(201);
    }

    /**
     * Mon compte
     */
    public function show(Request $request): AccountResource
    {
        return AccountResource::make($request->user());
    }

    /**
     * Modifier mon compte
     *
     * Prénom et nom facultatifs ; pseudo et adresse uniques. Une nouvelle adresse doit être
     * confirmée à nouveau : un lien part par e-mail.
     */
    public function update(UpdateAccountRequest $request, UpdateAccount $updateAccount): AccountResource
    {
        return AccountResource::make($updateAccount->handle($request->user(), $request->user(), $request->validated()));
    }

    /**
     * Changer mon mot de passe
     */
    public function updatePassword(UpdatePasswordRequest $request, UpdatePassword $updatePassword): AccountResource
    {
        return AccountResource::make($updatePassword->handle($request->user(), $request->validated('password')));
    }
}
