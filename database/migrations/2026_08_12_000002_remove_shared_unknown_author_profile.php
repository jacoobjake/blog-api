<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $unknownAuthorId = DB::table('author_profiles')
            ->where('name', 'Unknown Author')
            ->whereNull('user_id')
            ->value('id');

        if ($unknownAuthorId === null) {
            return;
        }

        DB::table('blogs')
            ->where('author_profile_id', $unknownAuthorId)
            ->update(['author_profile_id' => null]);

        DB::table('author_profiles')
            ->where('id', $unknownAuthorId)
            ->delete();
    }

    public function down(): void
    {
        // Data cleanup is not reversible.
    }
};
