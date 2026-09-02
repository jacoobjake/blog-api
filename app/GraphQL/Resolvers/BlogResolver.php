<?php

namespace App\GraphQL\Resolvers;

use App\Enums\Permission;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Database\Eloquent\Builder;

class BlogResolver
{
    /**
     * Return a builder scoped to published blogs only.
     * Used by @paginate(builder: ...) for the blogsPublic query.
     */
    public function publishedBuilder(mixed $_, array $args): Builder
    {
        return app(BlogService::class)->getQuery()->published();
    }

    /**
     * Return a builder for admin blog listings, scoped by ownership when needed.
     */
    public function adminBuilder(mixed $_, array $args): Builder
    {
        $query = app(BlogService::class)->getQuery();
        $user = auth()->user();

        if ($user && ! $user->can(Permission::BLOGS_VIEW_ANY->value)) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}
