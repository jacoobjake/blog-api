<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionAuthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // POST /admin/auth/session
    // -------------------------------------------------------------------------

    public function test_valid_credentials_create_session_and_return_user(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Use post() (not postJson()) so the StartSession middleware runs and
        // request()->session() is available. Accept header gets JSON responses.
        $response = $this->post('/api/admin/auth/session', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email']]]);
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/api/admin/auth/session', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ], ['Accept' => 'application/json']);

        $response->assertUnauthorized();
    }

    public function test_missing_fields_returns_422(): void
    {
        $response = $this->post('/api/admin/auth/session', [], ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // -------------------------------------------------------------------------
    // POST /admin/auth/invalidate
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_invalidate_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/admin/auth/invalidate', [], ['Accept' => 'application/json']);

        $response->assertOk();
    }

    public function test_unauthenticated_invalidate_returns_401(): void
    {
        $response = $this->post('/api/admin/auth/invalidate', [], ['Accept' => 'application/json']);

        $response->assertUnauthorized();
    }
}
