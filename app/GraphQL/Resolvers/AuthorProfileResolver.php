<?php

namespace App\GraphQL\Resolvers;

use App\Enums\Permission;
use App\Models\AuthorProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class AuthorProfileResolver
{
    public function builder(mixed $_, array $args): Builder
    {
        $query = AuthorProfile::query()->with('user');
        $user = auth()->user();

        if ($user && ! $user->can(Permission::AUTHORS_VIEW_ANY->value)) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function findAuthorProfile(mixed $_, array $args): AuthorProfile
    {
        $profile = AuthorProfile::query()
            ->with('user')
            ->findOrFail($args['id']);

        Gate::authorize('view', $profile);

        return $profile;
    }
}
