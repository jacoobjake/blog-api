<?php

namespace Tests\Feature\Blog;

use App\Models\Blog;
use App\Models\User;
use Tests\TestCase;

class CreateBlogTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'My First Blog',
            'json_content' => [
                'type' => 'compressed_base64',
                'body' => base64_encode('hello world'),
            ],
            'author' => 'Jake',
            'is_published' => false,
            'tags' => ['laravel', 'php'],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // POST /admin/blogs
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_create_blog(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $this->validPayload());

        $response->assertOk()
            ->assertJsonStructure(['data' => ['slug']]);
    }

    public function test_created_by_and_updated_by_are_set_to_auth_user(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $this->validPayload());

        $blog = Blog::first();
        $this->assertSame($this->user->id, $blog->created_by);
        $this->assertSame($this->user->id, $blog->updated_by);
    }

    public function test_tags_are_synced_on_create(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $this->validPayload(['tags' => ['laravel', 'php']]));

        $blog = Blog::first();
        $tagNames = $blog->tags->pluck('name')->toArray();

        $this->assertContains('laravel', $tagNames);
        $this->assertContains('php', $tagNames);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/admin/blogs', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_missing_title_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $this->validPayload(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_missing_json_content_returns_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['json_content']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['json_content']);
    }

    public function test_invalid_json_content_type_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $this->validPayload([
                'json_content' => ['type' => 'invalid_type', 'body' => 'abc'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['json_content.type']);
    }

    public function test_missing_is_published_returns_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['is_published']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_published']);
    }
}
