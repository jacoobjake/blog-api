<?php

namespace Tests\Unit\Services;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    private AssetService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->service = new AssetService();
        $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    public function test_store_auto_assigns_uuid(): void
    {
        $this->service->store(['type' => AssetType::Image->value]);

        $asset = $this->service->getModel();

        $this->assertNotNull($asset->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $asset->uuid,
        );
    }

    public function test_store_auto_assigns_user_id_from_auth(): void
    {
        $this->service->store(['type' => AssetType::Image->value]);

        $this->assertSame($this->user->id, $this->service->getModel()->user_id);
    }

    // -------------------------------------------------------------------------
    // setByUuid()
    // -------------------------------------------------------------------------

    public function test_set_by_uuid_loads_correct_asset(): void
    {
        $asset = Asset::factory()->forUser($this->user)->create();

        $this->service->setByUuid($asset->uuid);

        $this->assertSame($asset->id, $this->service->getModel()->id);
    }

    public function test_set_by_uuid_throws_for_unknown_uuid(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->setByUuid('00000000-0000-0000-0000-000000000000');
    }

    // -------------------------------------------------------------------------
    // uploadMedia()
    // -------------------------------------------------------------------------

    public function test_upload_media_adds_file_to_correct_collection(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->service->setModel($asset)->uploadMedia($file);

        $this->assertSame(1, $asset->fresh()->getMedia(AssetType::Image->value)->count());
    }

    public function test_upload_media_throws_when_model_not_initialized(): void
    {
        $this->expectException(\LogicException::class);

        $this->service->uploadMedia(UploadedFile::fake()->image('photo.jpg'));
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_removes_asset_from_database(): void
    {
        $asset = Asset::factory()->forUser($this->user)->create();

        $this->service->setModel($asset)->delete();

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_delete_clears_media_collection_before_deleting(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();
        $file = UploadedFile::fake()->image('photo.jpg');
        $this->service->setModel($asset)->uploadMedia($file);

        $this->assertSame(1, $asset->getMedia(AssetType::Image->value)->count());

        $this->service->delete();

        // After delete, media records should also be gone
        $this->assertDatabaseMissing('media', ['model_id' => $asset->id]);
    }

    public function test_delete_throws_when_model_not_initialized(): void
    {
        $this->expectException(\LogicException::class);

        $this->service->delete();
    }
}
