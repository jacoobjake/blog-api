<?php

namespace App\GraphQL\Builders;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AuthorProfile;
use App\Services\BlogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable @paginate builders that apply policy-based query scoping.
 */
class ScopedListBuilder
{
    public function blogs(mixed $_, array $args): Builder
    {
        return app(BlogService::class)->getQuery()->visibleToAuthenticatedUser();
    }

    public function authorProfiles(mixed $_, array $args): Builder
    {
        return $this->scopedQuery(AuthorProfile::class, fn (Builder $query) => $query->with('user'));
    }

    public function assets(mixed $_, array $args): Builder
    {
        $query = Asset::query()->ownedByAuthenticatedUser();

        if (isset($args['type'])) {
            $type = $args['type'];
            $query->where('type', $type instanceof AssetType ? $type->value : $type);
        }

        return $query;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  callable(Builder): Builder|null  $configure
     */
    protected function scopedQuery(string $modelClass, ?callable $configure = null): Builder
    {
        $query = $modelClass::query()->visibleToAuthenticatedUser();

        if ($configure !== null) {
            $query = $configure($query);
        }

        return $query;
    }
}
