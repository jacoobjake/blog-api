<?php

namespace Tests\Feature\Blog;

use App\Models\Asset;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateBlogWithHeroAssetTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_create_blog_with_hero_asset_and_description(): void
    {
        $file = UploadedFile::fake()->image('hero.jpg', 800, 400);

        $assetResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => 'image',
                'file' => $file,
            ]);

        $uuid = $assetResponse->json('data.uuid');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', [
                'title' => 'Blog With Hero',
                'description' => 'A short summary',
                'json_content' => [
                    'type' => 'compressed_base64',
                    'body' => base64_encode('hello world'),
                ],
                'hero_asset_uuid' => $uuid,
                'author' => 'Jake',
                'is_published' => true,
                'tags' => ['hero'],
            ]);

        $response->assertOk();

        $blog = Blog::first();
        $this->assertSame('A short summary', $blog->description);
        $this->assertSame($uuid, $blog->hero_asset_uuid);
    }

    public function test_cannot_assign_another_users_asset_as_hero(): void
    {
        $otherUser = User::factory()->create();
        $asset = Asset::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/admin/blogs', [
                'title' => 'Blog With Hero',
                'json_content' => [
                    'type' => 'compressed_base64',
                    'body' => base64_encode('hello world'),
                ],
                'hero_asset_uuid' => $asset->uuid,
                'author' => 'Jake',
                'is_published' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hero_asset_uuid']);
    }
}
