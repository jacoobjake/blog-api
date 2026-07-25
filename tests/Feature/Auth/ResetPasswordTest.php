<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ])->assertOk()
            ->assertJsonPath('message', __('passwords.reset'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass1!', $user->password));
    }

    public function test_invalid_token_returns_422(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ])->assertUnprocessable();
    }

    public function test_weak_password_returns_422(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
