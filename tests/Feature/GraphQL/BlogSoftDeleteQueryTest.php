<?php

namespace Tests\Feature\GraphQL;

use App\Models\Blog;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class BlogSoftDeleteQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_soft_deleted_blog_is_excluded_from_default_blogs_query(): void
    {
        $active = Blog::factory()->createdBy($this->user)->create(['title' => 'Active']);
        $deleted = Blog::factory()->createdBy($this->user)->create(['title' => 'Deleted']);
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogs(first: 10) {
                        data { slug title }
                    }
                }
            ');

        $titles = collect($response->json('data.blogs.data'))->pluck('title')->toArray();
        $this->assertSame(['Active'], $titles);
    }
}
