<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of \App\Models\Blog
 *
 * @extends Builder<TModel>
 */
class BlogBuilder extends Builder
{
    /**
     * Columns list queries need. `json_content` is omitted because Lighthouse
     * paginates with `['*']`, which would otherwise read the full document
     * for every row even when the client did not ask for it.
     *
     * @var list<string>
     */
    public const LISTING_COLUMNS = [
        'id',
        'title',
        'slug',
        'description',
        'hero_asset_uuid',
        'author_profile_id',
        'is_published',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @param  int|null  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  \Closure|int|null  $total
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if ($columns === ['*']) {
            $columns = array_map(
                fn (string $column) => $this->model->qualifyColumn($column),
                self::LISTING_COLUMNS,
            );
        }

        return parent::paginate($perPage, $columns, $pageName, $page, $total);
    }
}
