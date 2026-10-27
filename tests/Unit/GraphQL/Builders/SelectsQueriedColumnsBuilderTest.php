<?php

namespace Tests\Unit\GraphQL\Builders;

use App\GraphQL\Builders\SelectsQueriedColumnsBuilder;
use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SelectsQueriedColumnsBuilderTest extends TestCase
{
    public function test_blog_query_uses_the_default_eloquent_builder(): void
    {
        $this->assertInstanceOf(Builder::class, Blog::query());
        $this->assertNotInstanceOf(SelectsQueriedColumnsBuilder::class, Blog::query());
    }

    public function test_model_paginate_is_not_column_restricted(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        Blog::query()->paginate(5);

        $rowSelect = $this->blogRowSelectSql();

        $this->assertNotNull($rowSelect);
        $this->assertTrue(
            str_contains($rowSelect, '*') || str_contains($rowSelect, 'json_content'),
            "Expected SELECT * (or json_content) in: {$rowSelect}",
        );
    }

    public function test_wrap_keeps_soft_deletes_scope(): void
    {
        $active = Blog::factory()->create(['title' => 'Active']);
        $deleted = Blog::factory()->create(['title' => 'Deleted']);
        $deleted->delete();

        $titles = SelectsQueriedColumnsBuilder::wrap(Blog::query())
            ->forQueriedFields(['title'])
            ->paginate(10)
            ->getCollection()
            ->pluck('title')
            ->all();

        $this->assertSame([$active->title], $titles);
    }

    public function test_wrap_keeps_select_star_until_fields_are_recorded(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        SelectsQueriedColumnsBuilder::wrap(Blog::query())->paginate(5);

        $rowSelect = $this->blogRowSelectSql();

        $this->assertNotNull($rowSelect);
        $this->assertTrue(
            str_contains($rowSelect, '*') || str_contains($rowSelect, 'json_content'),
            "Expected SELECT * (or json_content) in: {$rowSelect}",
        );
    }

    public function test_wrap_selects_only_queried_fields(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        SelectsQueriedColumnsBuilder::wrap(Blog::query())
            ->forQueriedFields(['slug', 'title'])
            ->paginate(5);

        $rowSelect = $this->blogRowSelectSql();

        $this->assertNotNull($rowSelect);
        $this->assertStringNotContainsString('json_content', $rowSelect);
        $this->assertStringContainsString('slug', $rowSelect);
        $this->assertStringContainsString('title', $rowSelect);
        $this->assertStringContainsString('id', $rowSelect);
    }

    public function test_wrap_selects_belongs_to_foreign_keys(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        SelectsQueriedColumnsBuilder::wrap(Blog::query())
            ->forQueriedFields(['author_profile', 'hero_asset'])
            ->paginate(5);

        $rowSelect = $this->blogRowSelectSql();

        $this->assertNotNull($rowSelect);
        $this->assertStringContainsString('author_profile_id', $rowSelect);
        $this->assertStringContainsString('hero_asset_uuid', $rowSelect);
        $this->assertStringNotContainsString('json_content', $rowSelect);
    }

    private function blogRowSelectSql(): ?string
    {
        return collect(DB::getQueryLog())
            ->pluck('query')
            ->first(fn (string $query) => (str_contains(strtolower($query), 'from "blogs"')
                || str_contains(strtolower($query), 'from `blogs`'))
                && ! str_contains(strtolower($query), 'count('));
    }
}
