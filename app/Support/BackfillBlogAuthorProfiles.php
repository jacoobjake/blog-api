<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class BackfillBlogAuthorProfiles
{
    public function run(): void
    {
        $unknownAuthorId = DB::table('author_profiles')
            ->where('name', 'Unknown Author')
            ->whereNull('user_id')
            ->value('id');

        if ($unknownAuthorId === null) {
            $unknownAuthorId = DB::table('author_profiles')->insertGetId([
                'name' => 'Unknown Author',
                'bio' => null,
                'user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('blogs')
            ->select('id', 'created_by')
            ->whereNull('author_profile_id')
            ->orderBy('id')
            ->lazy()
            ->each(function ($blog) use ($unknownAuthorId) {
                $profileId = null;

                if ($blog->created_by !== null) {
                    $profileId = DB::table('author_profiles')
                        ->where('user_id', $blog->created_by)
                        ->value('id');
                }

                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update([
                        'author_profile_id' => $profileId ?? $unknownAuthorId,
                    ]);
            });
    }
}
