<?php

namespace Tests\Feature\GraphQL;

use App\Models\User;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class UserQueryTest extends TestCase
{
    use MakesGraphQLRequests;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // user(id:) / user(email:)
    // -------------------------------------------------------------------------

    public function test_can_query_user_by_id(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query ($id: ID) {
                     user(id: $id) { id email }
                 }
             ', ['id' => $this->user->id])
            ->assertJsonPath('data.user.id', (string) $this->user->id)
            ->assertJsonPath('data.user.email', $this->user->email);
    }

    public function test_can_query_user_by_email(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query ($email: String) {
                     user(email: $email) { id email }
                 }
             ', ['email' => $this->user->email])
            ->assertJsonPath('data.user.email', $this->user->email);
    }

    public function test_providing_both_id_and_email_returns_validation_error(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                 query ($id: ID, $email: String) {
                     user(id: $id, email: $email) { id }
                 }
             ', ['id' => $this->user->id, 'email' => $this->user->email])
            ->assertGraphQLErrorMessage('Validation failed for the field [user].');
    }

    public function test_unauthenticated_user_query_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID) {
                user(id: $id) { id }
            }
        ', ['id' => $this->user->id])
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    // -------------------------------------------------------------------------
    // users — paginated
    // -------------------------------------------------------------------------

    public function test_users_returns_paginated_list(): void
    {
        User::factory()->count(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->graphQL(/** @lang GraphQL */ '
                             query {
                                 users(first: 10) {
                                     data { id name email }
                                     paginatorInfo { total }
                                 }
                             }
                         ');

        // 2 created + 1 from setUp
        $response->assertJsonPath('data.users.paginatorInfo.total', 3);
    }

    public function test_unauthenticated_users_query_returns_error(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            query { users(first: 10) { data { id } } }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }
}
