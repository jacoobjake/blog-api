<?php

namespace App\GraphQL\Resolvers;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Database\Eloquent\Builder;

class AssetResolver
{
    /**
     * Query builder for the paginated `assets` field.
     * Lighthouse's @paginate directive calls this and handles pagination itself.
     *
     * @param  mixed  $_
     * @param  array{type?: string}  $args
     */
    public function userAssetsBuilder(mixed $_, array $args): Builder
    {
        $user = auth()->user();

        if (!$user) {
            throw new \LogicException("User is not authenticated");
        }

        return app(AssetService::class)->getQuery()
            ->where('user_id', $user->id)
            ->when(isset($args['type']), fn($q) => $q->where('type', $args['type']));
    }

    /**
     * @param  mixed  $_
     * @param  array{uuid: string}  $args
     */
    public function userAssetByUuid(mixed $_, array $args): Asset
    {
        $user = auth()->user();

        if (!$user) {
            throw new \LogicException("User is not authenticated");
        }

        return app(AssetService::class)->getQuery()
            ->where('uuid', $args['uuid'])
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    /**
     * Resolve the `media` field for an Asset.
     *
     * Returns an array with the original URL and Spatie conversion URLs
     * for the thumb_100 and thumb_200 conversions.
     *
     * @param  Asset  $asset
     * @return array{id: string, url: string, thumbnail_100: string, thumbnail_200: string}|null
     */
    public function media(Asset $asset): ?array
    {
        $media = $asset->getFirstMedia($asset->type->value);

        if (!$media) {
            return null;
        }

        return [
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'url' => $media->getUrl(),
            'thumbnail_100' => $media->getUrl('thumb_100'),
            'thumbnail_200' => $media->getUrl('thumb_200'),
        ];
    }
}
