<?php

namespace Database\Factories;

use App\Enums\BlogJsonContentType;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $authorProfile = AuthorProfile::factory()->create();

        return [
            'title' => fake()->sentence(4),
            'json_content' => [
                'type' => BlogJsonContentType::COMPRESSED_BASE64->value,
                'body' => base64_encode('test content'),
            ],
            'author_profile_id' => $authorProfile->id,
            'is_published' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => false,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function forAuthorProfile(AuthorProfile $authorProfile): static
    {
        return $this->state(fn(array $attributes) => [
            'author_profile_id' => $authorProfile->id,
        ]);
    }
}
