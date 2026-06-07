<?php

namespace Tests\Feature\GraphQL;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class AssetQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // asset(uuid:)
    // -------------------------------------------------------------------------

    public function test_owner_can_query_asset_by_uuid(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();

        $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query ($uuid: String!) {
                     asset(uuid: $uuid) {
                         uuid
                         type
                     }
                 }
             ', ['uuid' => $asset->uuid])
            ->assertJsonPath('data.asset.uuid', $asset->uuid)
            ->assertJsonPath('data.asset.type', 'Image');
    }

    public function test_querying_another_users_asset_returns_not_found(): void
    {
        $otherUser = User::factory()->create();
        $asset = Asset::factory()->image()->forUser($otherUser)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($uuid: String!) {
                                 asset(uuid: $uuid) { uuid }
                             }
                         ', ['uuid' => $asset->uuid]);

        // The resolver throws ModelNotFoundException which Lighthouse surfaces as an error
        $this->assertNotEmpty($response->json('errors'));
    }

    public function test_unauthenticated_asset_query_returns_error(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();

        $this->graphQL(/** @lang GraphQL */ '
            query ($uuid: String!) {
                asset(uuid: $uuid) { uuid }
            }
        ', ['uuid' => $asset->uuid])
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    // -------------------------------------------------------------------------
    // assets — paginated + filter
    // -------------------------------------------------------------------------

    public function test_assets_returns_only_authenticated_users_assets(): void
    {
        Asset::factory()->count(2)->image()->forUser($this->user)->create();
        Asset::factory()->image()->forUser(User::factory()->create())->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query {
                                 assets(first: 10) {
                                     data { uuid }
                                     paginatorInfo { total }
                                 }
                             }
                         ');

        $response->assertJsonPath('data.assets.paginatorInfo.total', 2);
    }

    public function test_assets_filter_by_type(): void
    {
        Asset::factory()->image()->forUser($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query {
                                 assets(first: 10, type: Image) {
                                     data { uuid type }
                                 }
                             }
                         ');

        $types = collect($response->json('data.assets.data'))->pluck('type')->toArray();
        $this->assertNotEmpty($types);
        foreach ($types as $type) {
            $this->assertSame('Image', $type);
        }
    }

    public function test_unauthenticated_assets_query_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query { assets(first: 10) { data { uuid } } }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }

    // -------------------------------------------------------------------------
    // media field
    // -------------------------------------------------------------------------

    public function test_media_field_resolves_all_fields_when_media_exists(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();
        $asset->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->toMediaCollection(AssetType::Image->value);

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($uuid: String!) {
                                 asset(uuid: $uuid) {
                                     media {
                                         url
                                         thumbnail_100
                                         thumbnail_200
                                         file_name
                                         mime_type
                                     }
                                 }
                             }
                         ', ['uuid' => $asset->uuid]);

        $media = $response->json('data.asset.media');
        $this->assertNotNull($media);
        $this->assertNotEmpty($media['url']);
        $this->assertNotEmpty($media['file_name']);
        $this->assertNotEmpty($media['mime_type']);
    }

    public function test_media_field_returns_null_when_no_media_attached(): void
    {
        $asset = Asset::factory()->image()->forUser($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query ($uuid: String!) {
                                 asset(uuid: $uuid) {
                                     media { url }
                                 }
                             }
                         ', ['uuid' => $asset->uuid]);

        $this->assertNull($response->json('data.asset.media'));
    }
}
