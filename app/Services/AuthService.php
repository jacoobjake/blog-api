<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(protected UserService $userService) {}

    /**
     * Verify user password
     * @param string $password
     * @param string $hashedPassword
     * @param bool $throws
     * @throws AuthenticationException
     * @return bool
     */
    public function verifyPassword(string $password, string $hashedPassword, bool $throws = true): bool
    {
        if (!Hash::check($password, $hashedPassword)) {
            if ($throws) {
                throw new AuthenticationException(__('auth.failed'));
            }
            return false;
        }
        return true;
    }

    public function token(string $email, string $password): string
    {
        $user = $this->userService->setByEmail($email)->getModel();

        $this->verifyPassword($password, $user->password);

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

    public function session(string $email, string $password): void
    {
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            logger()->info("success login", ['email' => $email]);
            logger()->info("user authenticated", request()->user()->toArray());
            request()->session()->regenerate();
            return;
        }

        throw new AuthenticationException(__('auth.failed'));
    }

    public function invalidateSession()
    {
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}