<?php

namespace Tests\Feature\Blog;

use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\User;
use App\Support\BackfillBlogAuthorProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class BackfillMissingBlogAuthorProfilesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_backfill_assigns_creator_author_profile_when_available(): void
    {
        $user = User::factory()->create();
        $profile = AuthorProfile::factory()->forUser($user)->create(['name' => 'Creator Author']);

        $blogId = DB::table('blogs')->insertGetId([
            'title' => 'Legacy Post',
            'slug' => 'legacy-post',
            'json_content' => json_encode(['type' => 'compressed_base64', 'body' => 'dGVzdA==']),
            'author_profile_id' => null,
            'is_published' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(BackfillBlogAuthorProfiles::class)->run();

        $this->assertDatabaseHas('blogs', [
            'id' => $blogId,
            'author_profile_id' => $profile->id,
        ]);
    }

    public function test_backfill_assigns_unknown_author_when_creator_has_no_profile(): void
    {
        $user = User::factory()->create();

        $blogId = DB::table('blogs')->insertGetId([
            'title' => 'Orphan Post',
            'slug' => 'orphan-post',
            'json_content' => json_encode(['type' => 'compressed_base64', 'body' => 'dGVzdA==']),
            'author_profile_id' => null,
            'is_published' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(BackfillBlogAuthorProfiles::class)->run();

        $unknownAuthorId = AuthorProfile::query()
            ->where('name', 'Unknown Author')
            ->whereNull('user_id')
            ->value('id');

        $this->assertNotNull($unknownAuthorId);
        $this->assertDatabaseHas('blogs', [
            'id' => $blogId,
            'author_profile_id' => $unknownAuthorId,
        ]);
    }

    public function test_public_blog_query_returns_legacy_post_after_backfill(): void
    {
        $user = User::factory()->create();
        $profile = AuthorProfile::factory()->forUser($user)->create(['name' => 'Legacy Author']);

        DB::table('blogs')->insert([
            'title' => 'Readable Legacy Post',
            'slug' => 'readable-legacy-post',
            'json_content' => json_encode(['type' => 'compressed_base64', 'body' => 'dGVzdA==']),
            'author_profile_id' => null,
            'is_published' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(BackfillBlogAuthorProfiles::class)->run();

        $this->graphQL(/** @lang GraphQL */ '
            query ($slug: String!) {
                blogPublic(slug: $slug) {
                    slug
                    author_profile { name }
                }
            }
        ', ['slug' => 'readable-legacy-post'])
            ->assertJsonPath('data.blogPublic.slug', 'readable-legacy-post')
            ->assertJsonPath('data.blogPublic.author_profile.name', $profile->name);
    }

    public function test_public_blog_query_allows_null_author_profile_before_backfill(): void
    {
        $user = User::factory()->create();

        Blog::withoutEvents(function () use ($user) {
            Blog::factory()->createdBy($user)->published()->create([
                'title' => 'Unlinked Post',
                'slug' => 'unlinked-post',
                'author_profile_id' => null,
            ]);
        });

        $this->graphQL(/** @lang GraphQL */ '
            query ($slug: String!) {
                blogPublic(slug: $slug) {
                    slug
                    author_profile { name }
                }
            }
        ', ['slug' => 'unlinked-post'])
            ->assertJsonPath('data.blogPublic.slug', 'unlinked-post')
            ->assertJsonPath('data.blogPublic.author_profile', null);
    }
}
