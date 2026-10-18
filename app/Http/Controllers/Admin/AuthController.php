<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    // Token based authentication
    public function token(LoginRequest $request)
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

    // Session based authentication
    public function session(LoginRequest $request)
    {
        $validated = $request->validated();

        $this->authService->session($validated['email'], $validated['password']);

        return $this->success(__('auth.session_created'), ['user' => new UserResource($request->user())]);
    }

    public function invalidateSession()
    {
        $this->authService->invalidateSession();

        return $this->success(__('auth.logged_out'));
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        return $this->success(__('passwords.sent'));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(__($status));
    }

}
