<?php

namespace App\GraphQL\Support;

use App\Models\Builders\PaginatedSelectionBuilder;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Execution\ResolveInfo;

final class PaginatedFieldSelection
{
    public static function apply(Builder $builder, ResolveInfo $resolveInfo): Builder
    {
        if (! $builder instanceof PaginatedSelectionBuilder) {
            return $builder;
        }

        $data = $resolveInfo->getFieldSelection(1)['data'] ?? null;

        if (! is_array($data)) {
            return $builder;
        }

        return $builder->forQueriedFields(array_keys($data));
    }
}
