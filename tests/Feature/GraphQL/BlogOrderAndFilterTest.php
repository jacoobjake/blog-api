<?php

namespace Tests\Feature\GraphQL;

use App\Models\Blog;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class BlogOrderAndFilterTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_blogs_filter_by_is_published(): void
    {
        Blog::factory()->createdBy($this->user)->published()->create(['title' => 'Live']);
        Blog::factory()->createdBy($this->user)->unpublished()->create(['title' => 'Draft']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query ($isPublished: Boolean) {
                    blogs(first: 10, is_published: $isPublished) {
                        data { title is_published }
                    }
                }
            ', ['isPublished' => true]);

        $data = $response->json('data.blogs.data');
        $this->assertCount(1, $data);
        $this->assertSame('Live', $data[0]['title']);
        $this->assertTrue($data[0]['is_published']);
    }

    public function test_blogs_order_by_title_ascending(): void
    {
        Blog::factory()->createdBy($this->user)->create(['title' => 'Zebra']);
        Blog::factory()->createdBy($this->user)->create(['title' => 'Alpha']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    blogs(first: 10, orderBy: [{ column: TITLE, order: ASC }]) {
                        data { title }
                    }
                }
            ');

        $titles = collect($response->json('data.blogs.data'))->pluck('title')->toArray();
        $this->assertSame(['Alpha', 'Zebra'], $titles);
    }

    public function test_blogs_public_order_by_created_at_descending(): void
    {
        $older = Blog::factory()->createdBy($this->user)->published()->create([
            'title' => 'Older',
            'created_at' => now()->subDay(),
        ]);
        $newer = Blog::factory()->createdBy($this->user)->published()->create([
            'title' => 'Newer',
            'created_at' => now(),
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                blogsPublic(first: 10, orderBy: [{ column: CREATED_AT, order: DESC }]) {
                    data { slug }
                }
            }
        ');

        $slugs = collect($response->json('data.blogsPublic.data'))->pluck('slug')->toArray();
        $this->assertSame([$newer->slug, $older->slug], $slugs);
    }
}
