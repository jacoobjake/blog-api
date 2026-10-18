<?php

namespace Tests\Feature\GraphQL;

use App\Models\AuthorProfile;
use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class AuthorProfileQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    public function test_superadmin_can_query_author_profile_by_id(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $user = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($user)->create([
            'name' => 'GraphQL Author',
            'bio' => 'Bio text',
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query ($id: ID!) {
                    authorProfile(id: $id) {
                        id
                        name
                        bio
                        user {
                            id
                            name
                            email
                        }
                    }
                }
            ', ['id' => (string) $profile->id])
            ->assertJsonPath('data.authorProfile.id', (string) $profile->id)
            ->assertJsonPath('data.authorProfile.name', 'GraphQL Author')
            ->assertJsonPath('data.authorProfile.user.email', $user->email);
    }

    public function test_author_can_query_own_profile_via_me(): void
    {
        $author = User::factory()->author()->create();
        $profile = AuthorProfile::factory()->forUser($author)->create([
            'name' => 'My Profile',
        ]);

        $this->actingAs($author, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    me {
                        author_profile {
                            id
                            name
                        }
                    }
                }
            ')
            ->assertJsonPath('data.me.author_profile.id', (string) $profile->id)
            ->assertJsonPath('data.me.author_profile.name', 'My Profile');
    }

    public function test_me_returns_null_author_profile_when_user_has_none(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query {
                    me {
                        author_profile {
                            id
                        }
                    }
                }
            ')
            ->assertJsonPath('data.me.author_profile', null);
    }

    public function test_author_cannot_query_other_author_profile(): void
    {
        $author = User::factory()->author()->create();
        $otherProfile = AuthorProfile::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                query ($id: ID!) {
                    authorProfile(id: $id) {
                        id
                    }
                }
            ', ['id' => (string) $otherProfile->id])
            ->assertGraphQLErrorMessage('This action is unauthorized.');
    }
}
