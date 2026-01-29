<?php

namespace App\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(protected UserService $userService) {}
    public function token(string $email, string $password): string
    {
        $user = $this->userService->findByEmailOrFail($email);

        if (!Hash::check($password, $user->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        return $user->createToken('api-token')->plainTextToken;
    }

    public function revokeCurrentToken()
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        $user->currentAccessToken()->delete();
    }

    public function revokeAllTokens()
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        $user->tokens()->delete();
    }

    public function revokeTokenById(string $tokenId)
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            throw new AuthenticationException(__('auth.token_not_found'));
        }

        $token->delete();
    }
}