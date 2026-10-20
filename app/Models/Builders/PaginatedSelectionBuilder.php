<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

/**
 * Eloquent builder that keeps `SELECT *` as the paginate default.
 *
 * When a GraphQL list query names specific `data` fields, Lighthouse still
 * calls `paginate($perPage, ['*'])`. `forQueriedFields()` records those fields
 * so this builder can replace `*` with the matching table columns.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
class PaginatedSelectionBuilder extends Builder
{
    /** @var list<string>|null */
    protected ?array $queriedFields = null;

    /**
     * @param  list<string>  $fields
     */
    public function forQueriedFields(array $fields): static
    {
        $this->queriedFields = $fields;

        return $this;
    }

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
        return parent::paginate($perPage, $this->columnsForPagination($columns), $pageName, $page, $total);
    }

    /**
     * @param  int|null  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return Paginator<int, TModel>
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return parent::simplePaginate($perPage, $this->columnsForPagination($columns), $pageName, $page);
    }

    /**
     * @param  array<int, string>|string  $columns
     * @return array<int, string>|string
     */
    protected function columnsForPagination(array|string $columns): array|string
    {
        if (! $this->wantsAllColumns($columns) || $this->queriedFields === null || $this->queriedFields === []) {
            return $columns;
        }

        return $this->columnsForQueriedFields($this->queriedFields);
    }

    /**
     * @param  array<int, string>|string  $columns
     */
    protected function wantsAllColumns(array|string $columns): bool
    {
        return $columns === ['*'] || $columns === '*';
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    protected function columnsForQueriedFields(array $fields): array
    {
        $columns = [$this->model->getKeyName()];

        foreach ($fields as $field) {
            if (! is_string($field) || str_starts_with($field, '__')) {
                continue;
            }

            $relationName = Str::camel($field);

            if ($this->model->isRelation($relationName)) {
                $relation = $this->model->{$relationName}();

                if ($relation instanceof BelongsTo || $relation instanceof MorphTo) {
                    $columns[] = $relation->getForeignKeyName();
                }

                continue;
            }

            if ($this->modelHasColumn($field)) {
                $columns[] = $field;
            }
        }

        return array_values(array_unique(array_map(
            fn (string $column) => $this->model->qualifyColumn($column),
            $columns,
        )));
    }

    protected function modelHasColumn(string $column): bool
    {
        $table = $this->model->getTable();
        $listing = $this->getQuery()->getConnection()->getSchemaBuilder()->getColumnListing($table);

        return in_array($column, $listing, true);
    }
}
