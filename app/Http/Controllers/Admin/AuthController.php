<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return $this->success(__('auth.token_created'), ['token' => $token]);
    }

    public function revoke()
    {
        $this->authService->revokeCurrentToken();

        return $this->success(__('auth.token_revoked'));
    }
}
