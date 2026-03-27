<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TokenAuthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // POST /admin/auth/token
    // -------------------------------------------------------------------------

    public function test_valid_credentials_return_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/admin/auth/token', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/admin/auth/token', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',  // 8+ chars to pass validation, but wrong
        ]);

        $response->assertUnauthorized();
    }

    public function test_non_existent_email_returns_error(): void
    {
        $response = $this->postJson('/api/admin/auth/token', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(404);
    }

    public function test_missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/admin/auth/token', [
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/admin/auth/token', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_missing_password_returns_422(): void
    {
        $response = $this->postJson('/api/admin/auth/token', [
            'email' => 'admin@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // POST /admin/auth/revoke
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/admin/auth/revoke');

        $response->assertOk();
    }

    public function test_unauthenticated_revoke_returns_401(): void
    {
        $response = $this->postJson('/api/admin/auth/revoke');

        $response->assertUnauthorized();
    }
}
