<?php

use App\Support\BackfillBlogAuthorProfiles;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        app(BackfillBlogAuthorProfiles::class)->run();
    }

    public function down(): void
    {
        // Data backfill is not reversible.
    }
};
