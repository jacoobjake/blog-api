<?php

namespace Database\Factories;

use App\Models\AuthorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorProfile>
 */
class AuthorProfileFactory extends Factory
{
    protected $model = AuthorProfile::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'bio' => fake()->optional()->sentence(),
        ];
    }
}
