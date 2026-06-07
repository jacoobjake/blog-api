<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    public function test_set_by_email_returns_static_with_user_set(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $result = $this->service->setByEmail('test@example.com');

        $this->assertInstanceOf(UserService::class, $result);
        $this->assertSame($user->id, $this->service->getModel()->id);
    }

    public function test_set_by_email_throws_for_unknown_email(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->setByEmail('nobody@example.com');
    }
}
