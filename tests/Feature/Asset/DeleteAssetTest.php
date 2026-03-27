<?php

namespace Tests\Feature\Asset;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteAssetTest extends TestCase
{
    private User $user;
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->asset = Asset::factory()->image()->forUser($this->user)->create();
    }

    // -------------------------------------------------------------------------
    // DELETE /assets/{uuid}
    // -------------------------------------------------------------------------

    public function test_owner_can_delete_their_asset(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/assets/{$this->asset->uuid}")
            ->assertOk();
    }

    public function test_asset_is_removed_from_database(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/assets/{$this->asset->uuid}");

        $this->assertDatabaseMissing('assets', ['id' => $this->asset->id]);
    }

    public function test_media_is_cleared_after_deletion(): void
    {
        // Attach media first
        $this->asset->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->toMediaCollection(AssetType::Image->value);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/assets/{$this->asset->uuid}");

        $this->assertDatabaseMissing('media', ['model_id' => $this->asset->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->deleteJson("/api/assets/{$this->asset->uuid}")
            ->assertUnauthorized();
    }

    public function test_non_owner_cannot_delete_asset(): void
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/assets/{$this->asset->uuid}")
            ->assertForbidden();
    }

    public function test_non_existent_uuid_returns_404(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/assets/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }
}
