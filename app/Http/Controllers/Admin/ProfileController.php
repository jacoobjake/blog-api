<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuthService;

class ProfileController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $validated = $request->validated();

        $this->authService->updatePassword(
            $request->user(),
            $validated['current_password'],
            $validated['password'],
        );

        return $this->success(__('auth.password_updated'));
    }
}
