<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->foreignUuid('hero_asset_uuid')
                ->nullable()
                ->after('json_content')
                ->constrained('assets', 'uuid')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hero_asset_uuid');
            $table->dropColumn('description');
        });
    }
};
