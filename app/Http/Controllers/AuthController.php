<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestTokenReques;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}
    public function token(RequestTokenReques $request)
    {
        $validated = $request->validated();

        $token = $this->authService->token($validated['email'], $validated['password']);

        return $this->success('Token generated successfully', ['token' => $token]);
    }

    public function revoke()
    {
        $this->authService->revokeCurrentToken();

        return $this->success('Token revoked successfully');
    }

    public function me(Request $request)
    {
        return $this->success('User retrieved successfully', new UserResource($request->user()));
    }
}
