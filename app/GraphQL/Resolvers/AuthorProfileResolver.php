<?php

namespace App\GraphQL\Resolvers;

use App\Enums\Permission;
use App\Models\AuthorProfile;
use Illuminate\Database\Eloquent\Builder;

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
}
