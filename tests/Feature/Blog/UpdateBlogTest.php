<?php

namespace Tests\Feature\Blog;

use App\Models\Blog;
use App\Models\User;
use Tests\TestCase;

class UpdateBlogTest extends TestCase
{
    private User $user;
    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->blog = Blog::factory()->createdBy($this->user)->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Updated Title',
            'json_content' => [
                'type' => 'compressed_base64',
                'body' => base64_encode('updated content'),
            ],
            'author' => 'Jake',
            'is_published' => true,
            'tags' => ['updated-tag'],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // PUT /admin/blogs/{slug}
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_update_blog(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/admin/blogs/{$this->blog->slug}", $this->validPayload());

        $response->assertOk()
            ->assertJsonStructure(['data' => ['slug']]);
    }

    public function test_updated_by_is_changed_to_auth_user(): void
    {
        $updater = User::factory()->create();

        $this->actingAs($updater, 'sanctum')
            ->putJson("/api/admin/blogs/{$this->blog->slug}", $this->validPayload());

        $this->assertSame($updater->id, $this->blog->fresh()->updated_by);
    }

    public function test_created_by_is_not_changed_on_update(): void
    {
        $updater = User::factory()->create();

        $this->actingAs($updater, 'sanctum')
            ->putJson("/api/admin/blogs/{$this->blog->slug}", $this->validPayload());

        $this->assertSame($this->user->id, $this->blog->fresh()->created_by);
    }

    public function test_tags_are_resynced_on_update(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/admin/blogs/{$this->blog->slug}", $this->validPayload(['tags' => ['new-tag']]));

        $tagNames = $this->blog->fresh()->tags->pluck('name')->toArray();

        $this->assertContains('new-tag', $tagNames);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->putJson("/api/admin/blogs/{$this->blog->slug}", $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_non_existent_slug_returns_404(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/admin/blogs/non-existent-slug', $this->validPayload())
            ->assertNotFound();
    }

    public function test_invalid_data_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/admin/blogs/{$this->blog->slug}", ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}
