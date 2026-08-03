<?php

namespace App\Http\Controllers;

use App\Actions\RegisterOwner;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Register a new owner: creates the account, their workspace and membership,
     * then issues an API token.
     */
    public function register(RegisterRequest $request, RegisterOwner $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('currentWorkspace'),
        ], 201);
    }
}
