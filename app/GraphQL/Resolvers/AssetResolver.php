<?php

namespace App\GraphQL\Resolvers;

use App\Models\Asset;

class AssetResolver
{
    /**
     * Resolve the `media` field for an Asset.
     *
     * Returns an array with the original URL and Spatie conversion URLs
     * for the thumb_100 and thumb_200 conversions.
     *
     * @return array{file_name: string, mime_type: string, url: string, thumbnail_100: string, thumbnail_200: string}|null
     */
    public function media(Asset $asset): ?array
    {
        $media = $asset->getFirstMedia($asset->type->value);

        if (! $media) {
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
