<?php

namespace Database\Factories;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'type' => AssetType::Image->value,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function image(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AssetType::Image->value,
        ]);
    }
}
