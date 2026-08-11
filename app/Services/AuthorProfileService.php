<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\AuthorProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthorProfileService extends BaseService
{
    protected static string $modelClass = AuthorProfile::class;

    public function createWithUserData(array $data): static
    {
        $profileData = Arr::only($data, ['name', 'bio']);
        $userData = $data['user'] ?? null;

        $this->model = AuthorProfile::create($profileData);

        if ($userData !== null) {
            $this->linkUser($userData);
        }

        return $this;
    }

    public function updateWithUserData(array $data): static
    {
        if (! $this->model) {
            throw new \LogicException('Model is not initialized');
        }

        $profileData = Arr::only($data, ['name', 'bio']);

        if ($profileData !== []) {
            $this->model->update($profileData);
        }

        if (array_key_exists('user', $data) && $data['user'] !== null) {
            $this->linkUser($data['user']);
        }

        return $this;
    }

    public function linkUser(array $userData): static
    {
        if (! $this->model) {
            throw new \LogicException('Model is not initialized');
        }

        $link = $userData['link'] ?? 'none';

        if ($link === 'none') {
            return $this;
        }

        if ($this->model->user_id !== null) {
            throw ValidationException::withMessages([
                'user' => __('validation.author_profile_user_already_linked'),
            ]);
        }

        if ($link === 'existing') {
            $userId = $userData['user_id'] ?? null;

            if ($userId === null) {
                throw ValidationException::withMessages([
                    'user.user_id' => __('validation.required', ['attribute' => 'user id']),
                ]);
            }

            if (AuthorProfile::query()->where('user_id', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user.user_id' => __('validation.author_profile_user_already_has_profile'),
                ]);
            }

            $this->model->update(['user_id' => $userId]);

            return $this;
        }

        if ($link === 'new') {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
            ]);

            $roles = $userData['roles'] ?? [Role::AUTHOR->value];
            $user->syncRoles($roles);

            $this->model->update(['user_id' => $user->id]);

            return $this;
        }

        throw ValidationException::withMessages([
            'user.link' => __('validation.in', ['attribute' => 'user link']),
        ]);
    }
}
