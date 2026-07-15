<?php

namespace App\Models;

use App\Enums\TagType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Blog extends Model
{
    use HasFactory;
    use HasSlug;
    use HasTags;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'json_content',
        'hero_asset_uuid',
        'author',
        'is_published',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'json_content' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function heroAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'hero_asset_uuid', 'uuid');
    }

    #[Scope]
    public function published(Builder $builder): void
    {
        $builder->where('is_published', true);
    }

    #[Scope]
    public function hasTags(Builder $builder, array $tags): void
    {
        $builder->withAnyTags($tags, TagType::BLOG->value);
    }
}
