<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\AuthorProfile;
use App\Models\User;

class AuthorProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AUTHORS_VIEW_ANY->value)
            || $user->can(Permission::AUTHORS_VIEW_OWN->value);
    }

    public function view(User $user, AuthorProfile $authorProfile): bool
    {
        return $user->can(Permission::AUTHORS_VIEW_ANY->value)
            || ($user->can(Permission::AUTHORS_VIEW_OWN->value)
                && $authorProfile->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AUTHORS_CREATE->value);
    }

    public function update(User $user, AuthorProfile $authorProfile): bool
    {
        return $user->can(Permission::AUTHORS_UPDATE_ANY->value)
            || ($user->can(Permission::AUTHORS_UPDATE_OWN->value)
                && $authorProfile->user_id === $user->id);
    }

    public function delete(User $user, AuthorProfile $authorProfile): bool
    {
        return $user->can(Permission::AUTHORS_DELETE_ANY->value);
    }
}
