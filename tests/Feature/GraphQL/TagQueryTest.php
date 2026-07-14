<?php

namespace Tests\Feature\GraphQL;

use App\Models\Blog;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class TagQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_blog_tags_returns_all_blog_tags_for_authenticated_user(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create();
        $blog->attachTag('laravel', 'blog');
        $blog->attachTag('php', 'blog');

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogTags { name }
                }
            ');

        $names = collect($response->json('data.blogTags'))->pluck('name')->sort()->values()->toArray();
        $this->assertSame(['laravel', 'php'], $names);
    }

    public function test_blog_tags_public_only_returns_tags_from_published_blogs(): void
    {
        $published = Blog::factory()->createdBy($this->user)->published()->create();
        $draft = Blog::factory()->createdBy($this->user)->unpublished()->create();
        $published->attachTag('public-tag', 'blog');
        $draft->attachTag('draft-only', 'blog');

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                blogTagsPublic { name }
            }
        ');

        $names = collect($response->json('data.blogTagsPublic'))->pluck('name')->toArray();
        $this->assertContains('public-tag', $names);
        $this->assertNotContains('draft-only', $names);
    }

    public function test_unauthenticated_blog_tags_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query { blogTags { name } }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }
}
