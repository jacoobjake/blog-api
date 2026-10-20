<?php

declare(strict_types=1);

namespace App\GraphQL\Directives;

use App\GraphQL\Support\PaginatedFieldSelection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Pass the selected `data` fields from a @paginate query onto the Eloquent builder.
 *
 * Lighthouse always paginates with `['*']`. Models that use
 * PaginatedSelectionBuilder can then select only the matching columns.
 */
class SelectQueriedColumnsDirective extends BaseDirective implements FieldBuilderDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Record the selected `data` fields on a paginated Eloquent builder.

Use with `@paginate` and a model whose builder is `PaginatedSelectionBuilder`.
When the client names specific item fields, those columns are selected.
When it does not, pagination keeps `SELECT *`.
"""
directive @selectQueriedColumns on FIELD_DEFINITION
GRAPHQL;
    }

    public function handleFieldBuilder(QueryBuilder|EloquentBuilder|Relation $builder, mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): QueryBuilder|EloquentBuilder|Relation
    {
        if (! $builder instanceof EloquentBuilder) {
            return $builder;
        }

        return PaginatedFieldSelection::apply($builder, $resolveInfo);
    }
}
