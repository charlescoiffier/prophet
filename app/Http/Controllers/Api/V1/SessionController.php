<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\CreateApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Comptes', weight: 4)]
class SessionController extends Controller
{
    /**
     * Se connecter
     *
     * Renvoie un jeton à passer ensuite dans l'en-tête « Authorization: Bearer ».
     *
     * @unauthenticated
     */
    public function store(LoginRequest $request, CreateApiToken $createApiToken): JsonResponse
    {
        $token = $createApiToken->handle(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return response()->json(['token' => $token], 201);
    }

    /**
     * Se déconnecter
     *
     * Révoque le jeton utilisé pour cette requête.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
