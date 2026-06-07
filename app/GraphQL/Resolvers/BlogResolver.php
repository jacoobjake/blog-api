<?php

namespace App\GraphQL\Resolvers;

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
}
