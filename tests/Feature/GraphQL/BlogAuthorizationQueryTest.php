<?php

namespace Tests\Feature\GraphQL;

use App\Models\Blog;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class BlogAuthorizationQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    public function test_author_only_sees_own_blogs_in_admin_list(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();

        $ownBlog = Blog::factory()->createdBy($author)->create(['title' => 'Own Post']);
        Blog::factory()->createdBy($otherAuthor)->create(['title' => 'Other Post']);

        $response = $this->actingAs($author, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogs(first: 10) {
                        data {
                            slug
                            title
                        }
                    }
                }
            ');

        $titles = collect($response->json('data.blogs.data'))->pluck('title');

        $this->assertTrue($titles->contains('Own Post'));
        $this->assertFalse($titles->contains('Other Post'));
    }

    public function test_editor_sees_all_blogs_in_admin_list(): void
    {
        $editor = User::factory()->editor()->create();
        $author = User::factory()->author()->create();

        Blog::factory()->createdBy($author)->create(['title' => 'Author Post']);

        $response = $this->actingAs($editor, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogs(first: 10) {
                        data {
                            title
                        }
                    }
                }
            ');

        $this->assertTrue(
            collect($response->json('data.blogs.data'))->pluck('title')->contains('Author Post')
        );
    }

    public function test_author_cannot_query_another_users_blog(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();
        $blog = Blog::factory()->createdBy($otherAuthor)->create();

        $this->actingAs($author, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query ($slug: String!) {
                    blog(slug: $slug) {
                        title
                    }
                }
            ', ['slug' => $blog->slug])
            ->assertGraphQLErrorMessage('This action is unauthorized.');
    }
}
