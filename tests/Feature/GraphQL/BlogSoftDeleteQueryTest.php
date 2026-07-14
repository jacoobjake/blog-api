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

    public function test_blogs_query_can_list_only_trashed_blogs(): void
    {
        $active = Blog::factory()->createdBy($this->user)->create(['title' => 'Active']);
        $deleted = Blog::factory()->createdBy($this->user)->create(['title' => 'Deleted']);
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogs(first: 10, trashed: ONLY) {
                        data { slug title deleted_at }
                    }
                }
            ');

        $response->assertGraphQLValidationPasses();
        $data = $response->json('data.blogs.data');
        $this->assertCount(1, $data);
        $this->assertSame('Deleted', $data[0]['title']);
        $this->assertNotNull($data[0]['deleted_at']);
    }
}
