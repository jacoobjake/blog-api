<?php

namespace App\Services;

use App\Models\User;

class UserService extends BaseService
{
    protected static string $modelClass = User::class;

    public function setByEmail(string $email): static
    {
        $user = static::$modelClass::where('email', $email)->firstOrFail();
        $this->setModel($user);

        return $this;
    }
}