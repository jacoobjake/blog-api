<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class BackfillBlogAuthorProfiles
{
    public function run(): void
    {
        DB::table('blogs')
            ->select('id', 'created_by')
            ->whereNull('author_profile_id')
            ->orderBy('id')
            ->lazy()
            ->each(function ($blog) {
                if ($blog->created_by === null) {
                    return;
                }

                $profileId = DB::table('author_profiles')
                    ->where('user_id', $blog->created_by)
                    ->value('id');

                if ($profileId === null) {
                    return;
                }

                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update(['author_profile_id' => $profileId]);
            });
    }
}
