<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/admin/profile/password', [
                'current_password' => 'OldPass1!',
                'password' => 'NewPass1!',
                'password_confirmation' => 'NewPass1!',
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass1!', $user->password));
    }

    public function test_wrong_current_password_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/admin/profile/password', [
                'current_password' => 'WrongPass1!',
                'password' => 'NewPass1!',
                'password_confirmation' => 'NewPass1!',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_weak_password_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/admin/profile/password', [
                'current_password' => 'OldPass1!',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->putJson('/api/admin/profile/password', [
            'current_password' => 'OldPass1!',
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response->assertUnauthorized();
    }
}
