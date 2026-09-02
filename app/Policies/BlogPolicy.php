<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::BLOGS_VIEW_ANY->value)
            || $user->can(Permission::BLOGS_VIEW_OWN->value);
    }

    public function view(User $user, Blog $blog): bool
    {
        return $user->can(Permission::BLOGS_VIEW_ANY->value)
            || ($user->can(Permission::BLOGS_VIEW_OWN->value) && $blog->created_by === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::BLOGS_CREATE->value);
    }

    public function update(User $user, Blog $blog): bool
    {
        return $user->can(Permission::BLOGS_UPDATE_ANY->value)
            || ($user->can(Permission::BLOGS_UPDATE_OWN->value) && $blog->created_by === $user->id);
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $user->can(Permission::BLOGS_DELETE_ANY->value)
            || ($user->can(Permission::BLOGS_DELETE_OWN->value) && $blog->created_by === $user->id);
    }
}
