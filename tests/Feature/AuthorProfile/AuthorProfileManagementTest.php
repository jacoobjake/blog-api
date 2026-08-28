<?php

namespace Tests\Feature\AuthorProfile;

use App\Models\AuthorProfile;
use App\Models\User;
use Tests\TestCase;

class AuthorProfileManagementTest extends TestCase
{
    public function test_superadmin_can_create_profile_without_user(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $response = $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/admin/authors', [
                'name' => 'Guest Author',
                'bio' => 'Writes sometimes',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.author.name', 'Guest Author');

        $this->assertDatabaseHas('author_profiles', [
            'name' => 'Guest Author',
            'user_id' => null,
        ]);
    }

    public function test_superadmin_can_create_profile_linked_to_existing_user(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $user = User::factory()->author()->create();

        $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/admin/authors', [
                'name' => 'Linked Author',
                'bio' => null,
                'user' => [
                    'link' => 'existing',
                    'user_id' => $user->id,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.author.user.id', $user->id);

        $this->assertDatabaseHas('author_profiles', [
            'name' => 'Linked Author',
            'user_id' => $user->id,
        ]);
    }

    public function test_superadmin_can_create_profile_with_new_user(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/admin/authors', [
                'name' => 'Brand New Author',
                'bio' => 'Fresh bio',
                'user' => [
                    'link' => 'new',
                    'name' => 'Brand New Author',
                    'email' => 'brandnew@example.com',
                    'password' => 'password123',
                    'roles' => ['author'],
                ],
            ])
            ->assertOk();

        $profile = AuthorProfile::query()->where('name', 'Brand New Author')->first();

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->user_id);
        $this->assertSame('brandnew@example.com', $profile->user->email);
    }

    public function test_superadmin_can_link_user_on_update(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $user = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->create(['name' => 'Unlinked Author']);

        $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/admin/authors/{$profile->id}", [
                'user' => [
                    'link' => 'existing',
                    'user_id' => $user->id,
                ],
            ])
            ->assertOk();

        $this->assertSame($user->id, $profile->fresh()->user_id);
    }

    public function test_superadmin_can_update_linked_profile_without_resending_user_link(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $user = User::factory()->superadmin()->create();
        $profile = AuthorProfile::factory()->forUser($user)->create([
            'name' => 'Super Admin',
            'bio' => 'Original bio',
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/admin/authors/{$profile->id}", [
                'name' => 'Super Admin',
                'bio' => 'Updated bio',
                'user' => [
                    'link' => 'existing',
                    'user_id' => $user->id,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.author.bio', 'Updated bio');

        $profile->refresh();

        $this->assertSame('Updated bio', $profile->bio);
        $this->assertSame($user->id, $profile->user_id);
    }

    public function test_superadmin_can_relink_profile_to_different_user(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $currentUser = User::factory()->author()->create();
        $newUser = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($currentUser)->create([
            'name' => 'Reassignable Author',
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/admin/authors/{$profile->id}", [
                'user' => [
                    'link' => 'existing',
                    'user_id' => $newUser->id,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.author.user.id', $newUser->id);

        $this->assertSame($newUser->id, $profile->fresh()->user_id);
    }

    public function test_superadmin_can_unlink_profile_user(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $user = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($user)->create([
            'name' => 'Linked Author',
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/admin/authors/{$profile->id}", [
                'user' => [
                    'link' => 'none',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.author.user', null);

        $this->assertNull($profile->fresh()->user_id);
    }

    public function test_author_can_view_and_update_own_profile(): void
    {
        $author = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($author)->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/admin/authors/me')
            ->assertOk()
            ->assertJsonPath('data.author.id', $profile->id);

        $this->actingAs($author, 'sanctum')
            ->putJson('/api/admin/authors/me', [
                'name' => 'Updated Name',
                'bio' => 'Updated bio',
            ])
            ->assertOk()
            ->assertJsonPath('data.author.name', 'Updated Name');

        $this->assertSame('Updated Name', $profile->fresh()->name);
    }

    public function test_author_cannot_manage_other_profiles(): void
    {
        $author = User::factory()->author()->create();
        $otherProfile = AuthorProfile::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/admin/authors/{$otherProfile->id}")
            ->assertForbidden();
    }

    public function test_author_cannot_create_profiles(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/admin/authors', [
                'name' => 'Should Fail',
            ])
            ->assertForbidden();
    }

    public function test_author_can_only_assign_own_profile_to_blog(): void
    {
        $author = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($author)->create();
        $otherProfile = AuthorProfile::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/admin/blogs', [
                'title' => 'Own Profile Blog',
                'json_content' => [
                    'type' => 'compressed_base64',
                    'body' => base64_encode('hello'),
                ],
                'author_profile_id' => $profile->id,
                'is_published' => false,
            ])
            ->assertOk();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/admin/blogs', [
                'title' => 'Other Profile Blog',
                'json_content' => [
                    'type' => 'compressed_base64',
                    'body' => base64_encode('hello'),
                ],
                'author_profile_id' => $otherProfile->id,
                'is_published' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['author_profile_id']);
    }
}
