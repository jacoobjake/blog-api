<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByEmailOrFail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }
}