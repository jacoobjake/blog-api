<?php

namespace App\GraphQL\Resolvers;

use App\GraphQL\Support\PaginatedFieldSelection;
use App\Services\BlogService;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class BlogResolver
{
    /**
     * Return a builder scoped to published blogs only.
     * Used by @paginate(builder: ...) for the blogsPublic query.
     */
    public function publishedBuilder(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Builder
    {
        return PaginatedFieldSelection::apply(
            app(BlogService::class)->getQuery()->published(),
            $resolveInfo,
        );
    }
}
