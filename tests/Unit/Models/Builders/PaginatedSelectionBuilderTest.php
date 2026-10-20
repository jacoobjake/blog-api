<?php

namespace Tests\Unit\Models\Builders;

use App\Models\Blog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaginatedSelectionBuilderTest extends TestCase
{
    public function test_paginate_defaults_to_all_columns(): void
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

    public function test_paginate_selects_only_queried_fields(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        Blog::query()->forQueriedFields(['slug', 'title'])->paginate(5);

        $rowSelect = $this->blogRowSelectSql();

        $this->assertNotNull($rowSelect);
        $this->assertStringNotContainsString('json_content', $rowSelect);
        $this->assertStringContainsString('slug', $rowSelect);
        $this->assertStringContainsString('title', $rowSelect);
        $this->assertStringContainsString('id', $rowSelect);
    }

    public function test_paginate_selects_belongs_to_foreign_keys(): void
    {
        Blog::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        Blog::query()->forQueriedFields(['author_profile', 'hero_asset'])->paginate(5);

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
