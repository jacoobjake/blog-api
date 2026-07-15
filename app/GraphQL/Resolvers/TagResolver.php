<?php

namespace App\GraphQL\Resolvers;

use App\Enums\TagType;
use App\Models\Blog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class TagResolver
{
    public function blogTags(): Collection
    {
        return Tag::withType(TagType::BLOG->value)->get();
    }

    public function publicBlogTags(): Collection
    {
        $tagIds = DB::table('taggables')
            ->join('blogs', function ($join) {
                $join->on('blogs.id', '=', 'taggables.taggable_id')
                    ->where('taggables.taggable_type', Blog::class)
                    ->whereNull('blogs.deleted_at');
            })
            ->where('blogs.is_published', true)
            ->distinct()
            ->pluck('taggables.tag_id');

        return Tag::query()
            ->whereIn('id', $tagIds)
            ->withType(TagType::BLOG->value)
            ->get();
    }
}
