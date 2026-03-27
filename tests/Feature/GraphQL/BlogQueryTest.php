<?php

namespace Tests\Feature\GraphQL;

use App\Models\Blog;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class BlogQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // blog(slug:) — authenticated
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_query_blog_by_slug(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->create(['title' => 'Hello World']);

        $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query ($slug: String!) {
                     blog(slug: $slug) {
                         slug
                         title
                     }
                 }
             ', ['slug' => $blog->slug])
            ->assertJsonPath('data.blog.slug', $blog->slug)
            ->assertJsonPath('data.blog.title', 'Hello World');
    }

    public function test_unauthenticated_blog_query_returns_error(): void
    {
        $blog = Blog::factory()->create();

        $this->graphQL(/** @lang GraphQL */ '
            query ($slug: String!) {
                blog(slug: $slug) { slug }
            }
        ', ['slug' => $blog->slug])
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    // -------------------------------------------------------------------------
    // blogs — paginated + filters
    // -------------------------------------------------------------------------

    public function test_blogs_returns_paginated_results(): void
    {
        Blog::factory()->count(3)->createdBy($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query {
                                 blogs(first: 10) {
                                     data { slug title }
                                     paginatorInfo { total }
                                 }
                             }
                         ');

        $response->assertJsonPath('data.blogs.paginatorInfo.total', 3);
    }

    public function test_blogs_filter_by_title(): void
    {
        Blog::factory()->createdBy($this->user)->create(['title' => 'Laravel Tips']);
        Blog::factory()->createdBy($this->user)->create(['title' => 'Vue Guide']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($title: String) {
                                 blogs(first: 10, title: $title) {
                                     data { title }
                                 }
                             }
                         ', ['title' => '%Laravel%']);

        $data = $response->json('data.blogs.data');
        $this->assertCount(1, $data);
        $this->assertSame('Laravel Tips', $data[0]['title']);
    }

    public function test_blogs_filter_by_author(): void
    {
        Blog::factory()->createdBy($this->user)->create(['author' => 'Jake']);
        Blog::factory()->createdBy($this->user)->create(['author' => 'Alice']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($author: String) {
                                 blogs(first: 10, author: $author) {
                                     data { author }
                                 }
                             }
                         ', ['author' => '%Jake%']);

        $data = $response->json('data.blogs.data');
        $this->assertCount(1, $data);
        $this->assertSame('Jake', $data[0]['author']);
    }

    public function test_blogs_filter_by_tags(): void
    {
        $tagged = Blog::factory()->createdBy($this->user)->create();
        $untagged = Blog::factory()->createdBy($this->user)->create();
        $tagged->attachTag('laravel', 'blog');

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($tags: [String!]) {
                                 blogs(first: 10, hasTags: $tags) {
                                     data { slug }
                                 }
                             }
                         ', ['tags' => ['laravel']]);

        $slugs = collect($response->json('data.blogs.data'))->pluck('slug')->toArray();
        $this->assertContains($tagged->slug, $slugs);
        $this->assertNotContains($untagged->slug, $slugs);
    }

    public function test_unauthenticated_blogs_query_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query { blogs(first: 10) { data { slug } } }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }

    // -------------------------------------------------------------------------
    // blogPublic / blogsPublic — no auth required
    // -------------------------------------------------------------------------

    public function test_blog_public_returns_published_blog(): void
    {
        $blog = Blog::factory()->createdBy($this->user)->published()->create();

        $this->graphQL(/** @lang GraphQL */ '
            query ($slug: String!) {
                blogPublic(slug: $slug) { slug is_published }
            }
        ', ['slug' => $blog->slug])
            ->assertJsonPath('data.blogPublic.slug', $blog->slug)
            ->assertJsonPath('data.blogPublic.is_published', true);
    }

    public function test_blogs_public_does_not_return_unpublished_blogs(): void
    {
        Blog::factory()->createdBy($this->user)->published()->create(['title' => 'Published']);
        Blog::factory()->createdBy($this->user)->unpublished()->create(['title' => 'Draft']);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                blogsPublic(first: 10) {
                    data { title is_published }
                }
            }
        ');

        $published = collect($response->json('data.blogsPublic.data'));

        // All returned blogs must have is_published = true
        $published->each(fn($blog) => $this->assertTrue($blog['is_published']));
        $this->assertContains('Published', $published->pluck('title')->toArray());
        $this->assertNotContains('Draft', $published->pluck('title')->toArray());
    }
}
