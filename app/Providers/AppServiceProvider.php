<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\User;
use App\Policies\AssetPolicy;
use App\Policies\AuthorProfilePolicy;
use App\Policies\BlogPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Blog::class, BlogPolicy::class);
        Gate::policy(AuthorProfile::class, AuthorProfilePolicy::class);
        Gate::policy(\App\Models\Asset::class, AssetPolicy::class);

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(Role::SUPERADMIN->value) ? true : null;
        });
    }
}
