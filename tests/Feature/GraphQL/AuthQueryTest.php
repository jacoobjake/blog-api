<?php

namespace Tests\Feature\GraphQL;

use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class AuthQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    // -------------------------------------------------------------------------
    // me query
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_query_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query {
                     me {
                         id
                         name
                         email
                     }
                 }
             ')
            ->assertJsonPath('data.me.id', (string) $user->id)
            ->assertJsonPath('data.me.email', $user->email);
    }

    public function test_unauthenticated_me_query_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query {
                me {
                    id
                }
            }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }
}
