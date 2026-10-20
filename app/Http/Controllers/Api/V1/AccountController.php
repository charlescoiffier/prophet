<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
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
}
