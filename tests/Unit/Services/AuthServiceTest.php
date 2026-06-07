<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService(new UserService());
    }

    // -------------------------------------------------------------------------
    // verifyPassword()
    // -------------------------------------------------------------------------

    public function test_verify_password_returns_true_when_matching(): void
    {
        $hashed = Hash::make('secret123');

        $this->assertTrue($this->service->verifyPassword('secret123', $hashed));
    }

    public function test_verify_password_throws_when_wrong_and_throws_is_true(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->service->verifyPassword('wrongpass', Hash::make('secret123'));
    }

    public function test_verify_password_returns_false_when_wrong_and_throws_is_false(): void
    {
        $hashed = Hash::make('secret123');

        $result = $this->service->verifyPassword('wrongpass', $hashed, throws: false);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // token()
    // -------------------------------------------------------------------------

    public function test_token_returns_plain_text_sanctum_token(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $this->service->token('user@example.com', 'password123');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_token_throws_for_wrong_password(): void
    {
        $this->expectException(AuthenticationException::class);

        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct'),
        ]);

        $this->service->token('user@example.com', 'wrong');
    }

    public function test_token_throws_for_unknown_email(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->token('nobody@example.com', 'password');
    }

    // -------------------------------------------------------------------------
    // revokeCurrentToken()
    // -------------------------------------------------------------------------

    public function test_revoke_current_token_deletes_token(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('api-token');

        // Authenticate via the real token so currentAccessToken() is populated
        $this->withToken($tokenResult->plainTextToken);
        $this->actingAs($user, 'sanctum');

        // Re-resolve auth user with token context via a real request
        $request = \Illuminate\Http\Request::create('/');
        $request->headers->set('Authorization', 'Bearer ' . $tokenResult->plainTextToken);
        app()->instance('request', $request);
        \Laravel\Sanctum\Sanctum::actingAs($user, [], 'sanctum');

        // Simulate what the route does: authenticate with the token
        $userWithToken = User::factory()->create();
        $t = $userWithToken->createToken('api-token');
        auth('sanctum')->setUser($userWithToken);
        $userWithToken->withAccessToken($t->accessToken);

        $this->service->revokeCurrentToken();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $t->accessToken->id]);
    }

    public function test_revoke_current_token_throws_when_unauthenticated(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->service->revokeCurrentToken();
    }

    // -------------------------------------------------------------------------
    // revokeAllTokens()
    // -------------------------------------------------------------------------

    public function test_revoke_all_tokens_deletes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('token-1');
        $user->createToken('token-2');
        $this->actingAs($user, 'sanctum');

        $this->service->revokeAllTokens();

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_revoke_all_tokens_throws_when_unauthenticated(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->service->revokeAllTokens();
    }

    // -------------------------------------------------------------------------
    // revokeTokenById()
    // -------------------------------------------------------------------------

    public function test_revoke_token_by_id_deletes_specific_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('specific-token');
        $this->actingAs($user, 'sanctum');

        $this->service->revokeTokenById((string) $token->accessToken->id);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_revoke_token_by_id_throws_for_unknown_token_id(): void
    {
        $this->expectException(AuthenticationException::class);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->service->revokeTokenById('99999');
    }

    public function test_revoke_token_by_id_throws_when_unauthenticated(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->service->revokeTokenById('1');
    }

    // -------------------------------------------------------------------------
    // invalidateSession()
    // -------------------------------------------------------------------------

    public function test_invalidate_session_completes_without_error(): void
    {
        // Bind a request with a real session store so invalidateSession() doesn't throw
        $handler = new \Illuminate\Session\FileSessionHandler(
            app('files'),
            storage_path('framework/sessions'),
            120,
        );
        $session = new \Illuminate\Session\Store('test', $handler);
        $session->start();

        $request = \Illuminate\Http\Request::create('/');
        $request->setLaravelSession($session);
        app()->instance('request', $request);

        $this->service->invalidateSession();
        $this->assertTrue(true);
    }
}
