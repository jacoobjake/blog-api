<?php

namespace Tests\Feature\Blog;

use App\Models\Blog;
use App\Models\User;
use Tests\TestCase;

class SuperadminBlogAccessTest extends TestCase
{
    public function test_superadmin_can_update_blog_without_role_permissions(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $author = User::factory()->author()->create();
        $blog = Blog::factory()->createdBy($author)->create();

        $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/admin/blogs/{$blog->slug}", [
                'title' => 'Updated by superadmin',
                'json_content' => [
                    'type' => 'compressed_base64',
                    'body' => base64_encode('updated'),
                ],
                'author_profile' => [
                    'name' => 'Superadmin Author',
                    'bio' => 'Platform owner',
                ],
                'is_published' => true,
                'tags' => [],
            ])
            ->assertOk();

        $blog->refresh();
        $this->assertSame('Updated by superadmin', $blog->title);
        $this->assertSame('Superadmin Author', $blog->authorProfile->name);
    }
}
