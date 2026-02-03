<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;

class AuthResolver
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function me(): User
    {
        return auth()->user();
    }
}
