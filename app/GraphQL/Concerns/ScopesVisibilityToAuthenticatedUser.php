<?php

namespace App\GraphQL\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesVisibilityToAuthenticatedUser
{
    public function scopeVisibleToAuthenticatedUser(Builder $builder): void
    {
        $user = auth()->user();

        if ($user === null || $user->can(static::viewAnyPermission())) {
            return;
        }

        static::restrictToOwnedRecords($builder, $user);
    }

    abstract protected static function viewAnyPermission(): string;

    abstract protected static function restrictToOwnedRecords(Builder $builder, User $user): void;
}
