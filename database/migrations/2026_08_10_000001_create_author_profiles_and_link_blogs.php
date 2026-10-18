<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('author_profile_id')
                ->nullable()
                ->after('hero_asset_uuid')
                ->constrained()
                ->nullOnDelete();
        });

        $profilesByName = [];

        DB::table('blogs')
            ->select('id', 'author')
            ->orderBy('id')
            ->lazy()
            ->each(function ($blog) use (&$profilesByName) {
                $name = trim((string) $blog->author) ?: 'Unknown Author';

                if (! isset($profilesByName[$name])) {
                    $profilesByName[$name] = DB::table('author_profiles')->insertGetId([
                        'name' => $name,
                        'bio' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update(['author_profile_id' => $profilesByName[$name]]);
            });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('author');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('author')->default('Unknown Author');
        });

        DB::table('blogs')
            ->select('blogs.id', 'author_profiles.name')
            ->join('author_profiles', 'author_profiles.id', '=', 'blogs.author_profile_id')
            ->orderBy('blogs.id')
            ->lazy()
            ->each(function ($blog) {
                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update(['author' => $blog->name]);
            });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_profile_id');
        });

        Schema::dropIfExists('author_profiles');
    }
};
