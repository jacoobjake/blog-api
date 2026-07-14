<?php

namespace Tests\Feature\Blog;

use App\Models\Blog;
use App\Models\User;
use Tests\TestCase;

class RestoreBlogTest extends TestCase
{
    private User $user;
    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->blog = Blog::factory()->createdBy($this->user)->create();
        $this->blog->delete();
    }

    public function test_authenticated_user_can_restore_soft_deleted_blog(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/blogs/{$this->blog->slug}/restore")
            ->assertOk();

        $this->assertNull($this->blog->fresh()->deleted_at);
    }

    public function test_restore_non_deleted_blog_returns_404(): void
    {
        $activeBlog = Blog::factory()->createdBy($this->user)->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/admin/blogs/{$activeBlog->slug}/restore")
            ->assertNotFound();
    }

    public function test_unauthenticated_restore_returns_401(): void
    {
        $this->postJson("/api/admin/blogs/{$this->blog->slug}/restore")
            ->assertUnauthorized();
    }
}
