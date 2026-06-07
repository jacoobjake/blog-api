<?php

namespace Tests\Feature\Blog;

use App\Models\Blog;
use App\Models\User;
use Tests\TestCase;

class DeleteBlogTest extends TestCase
{
    private User $user;
    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->blog = Blog::factory()->createdBy($this->user)->create();
    }

    // -------------------------------------------------------------------------
    // DELETE /admin/blogs/{slug}
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_delete_blog(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/admin/blogs/{$this->blog->slug}")
            ->assertOk();
    }

    public function test_blog_is_removed_from_database_after_delete(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/admin/blogs/{$this->blog->slug}");

        $this->assertDatabaseMissing('blogs', ['id' => $this->blog->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->deleteJson("/api/admin/blogs/{$this->blog->slug}")
            ->assertUnauthorized();
    }

    public function test_non_existent_slug_returns_404(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/admin/blogs/non-existent-slug')
            ->assertNotFound();
    }
}
