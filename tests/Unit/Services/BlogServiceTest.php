<?php

namespace Tests\Unit\Services;

use App\Enums\TagType;
use App\Models\Blog;
use App\Models\User;
use App\Services\BlogService;
use Tests\TestCase;

class BlogServiceTest extends TestCase
{
    private BlogService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = new BlogService();
        $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    public function test_store_sets_created_by_and_updated_by_to_auth_user(): void
    {
        $this->service->store([
            'title' => 'My Blog',
            'json_content' => ['type' => 'compressed_base64', 'body' => 'abc'],
            'author' => 'Author',
            'is_published' => false,
        ]);

        $blog = $this->service->getModel();

        $this->assertSame($this->user->id, $blog->created_by);
        $this->assertSame($this->user->id, $blog->updated_by);
    }

    public function test_store_persists_the_blog(): void
    {
        $this->service->store([
            'title' => 'Persisted Blog',
            'json_content' => ['type' => 'compressed_base64', 'body' => 'abc'],
            'author' => 'Author',
            'is_published' => false,
        ]);

        $this->assertDatabaseHas('blogs', ['title' => 'Persisted Blog']);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_sets_updated_by_to_auth_user(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();
        $updater = User::factory()->create();
        $this->actingAs($updater);

        $this->service->setModel($blog)->update(['title' => 'New Title']);

        $this->assertSame($updater->id, $this->service->getModel()->updated_by);
    }

    public function test_update_does_not_change_created_by(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();

        $this->service->setModel($blog)->update(['title' => 'Changed']);

        $this->assertSame($this->user->id, $this->service->getModel()->created_by);
    }

    // -------------------------------------------------------------------------
    // syncTags()
    // -------------------------------------------------------------------------

    public function test_sync_tags_attaches_tags_with_blog_type(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();
        $this->service->setModel($blog)->syncTags(['laravel', 'php']);

        $tagNames = $blog->fresh()->tagsWithType(TagType::BLOG->value)->pluck('name')->toArray();

        $this->assertContains('laravel', $tagNames);
        $this->assertContains('php', $tagNames);
    }

    public function test_sync_tags_replaces_old_tags(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();
        $this->service->setModel($blog)->syncTags(['old-tag']);
        $this->service->syncTags(['new-tag']);

        $tagNames = $blog->fresh()->tagsWithType(TagType::BLOG->value)->pluck('name')->toArray();

        $this->assertContains('new-tag', $tagNames);
        $this->assertNotContains('old-tag', $tagNames);
    }

    public function test_sync_tags_with_empty_array_removes_all_tags(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();
        $this->service->setModel($blog)->syncTags(['some-tag']);
        $this->service->syncTags([]);

        $tagNames = $blog->fresh()->tagsWithType(TagType::BLOG->value)->pluck('name')->toArray();

        $this->assertEmpty($tagNames);
    }

    // -------------------------------------------------------------------------
    // Blog model relationships
    // -------------------------------------------------------------------------

    public function test_created_by_relationship_returns_correct_user(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();

        $this->assertSame($this->user->id, $blog->createdBy->id);
    }

    public function test_updated_by_relationship_returns_correct_user(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();

        $this->assertSame($this->user->id, $blog->updatedBy->id);
    }
}
