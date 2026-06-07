<?php

namespace Tests\Feature\Asset;

use App\Enums\AssetType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadAssetTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // POST /assets
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_upload_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => AssetType::Image->value,
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['uuid']]);
    }

    public function test_media_record_is_created_after_upload(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => AssetType::Image->value,
                'file' => $file,
            ]);

        $uuid = $response->json('data.uuid');

        $this->assertDatabaseHas('assets', ['uuid' => $uuid]);
        $this->assertDatabaseHas('media', ['collection_name' => AssetType::Image->value]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/assets', [
            'type' => AssetType::Image->value,
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertUnauthorized();
    }

    public function test_invalid_type_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => 'invalid_type',
                'file' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_wrong_mime_type_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => AssetType::Image->value,
                'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_over_max_size_returns_422(): void
    {
        // 10241 KB > 10240 KB limit
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => AssetType::Image->value,
                'file' => UploadedFile::fake()->image('big.jpg')->size(10241),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_missing_file_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/assets', [
                'type' => AssetType::Image->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
