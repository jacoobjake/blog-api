<?php

namespace App\Models;

use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Asset extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
    ];

    public function casts(): array
    {
        return [
            'type' => AssetType::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(AssetType::Image->value)
            ->singleFile()
            ->acceptsMimeTypes(AssetType::Image->acceptedMimeTypes())
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb_100')
                    ->width(100)
                    ->height(100)
                    ->fit(Fit::Crop);
                $this->addMediaConversion('thumb_200')
                    ->width(200)
                    ->height(200)
                    ->fit(Fit::Crop);
            });
    }
}
