<?php

declare(strict_types=1);

namespace App\GraphQL\Directives;

use App\GraphQL\Builders\SelectsQueriedColumnsBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * GraphQL decorator for @paginate fields.
 *
 * Wraps the field's Eloquent builder for this query only and, when the client
 * names `data` fields, selects the matching columns instead of `*`.
 */
class SelectQueriedColumnsDirective extends BaseDirective implements FieldBuilderDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Select only the columns implied by a paginated list's `data` fields.

Use on `@paginate` fields. The model keeps its normal Eloquent builder.
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

        $builder = SelectsQueriedColumnsBuilder::wrap($builder);

        $data = $resolveInfo->getFieldSelection(1)['data'] ?? null;

        if (! is_array($data)) {
            return $builder;
        }

        return $builder->forQueriedFields(array_keys($data));
    }
}
