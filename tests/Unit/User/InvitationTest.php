<?php

namespace Tests\Unit\User;

use App\Models\Collaboration\Invitation;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Tests\Unit\Traits\AssertsListing;
use Arr, Str;

class InvitationTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    protected $roleRepository;

    protected $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleRepository = app('role.repository');
        $this->userRepository = app('user.repository');
    }

    /**
     * Test Invitation Listing.
     *
     * @return void
     */
    public function testInvitationListing()
    {
        $response = $this->getJson(
            url('api/invitations'),
            $this->authorizationHeader
        );

        $this->assertListing($response);

        $query = http_build_query(['search' => Str::random(10), 'order_by_created_at' => 'asc', 'order_by_name' => 'desc']);

        $response = $this->getJson(url('api/invitations?' . $query));

        $response->assertOk();
    }

    /**
     * Invitation creating test with valid attributes.
     *
     * @return void
     */
    public function testInvitationCreating()
    {
        $attributes = $this->makeGenericInvitationAttributes();

        $response = $this->postJson(url('api/users'), $attributes);

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'email', 'host', 'role_id', 'user_id', 'invitation_token', 'expires_at', 'created_at', 'role_name', 'url'
            ]);
    }

    /**
     * Create Invitation and make ensure that we can signup by a given invitation.
     *
     * @return void
     */
    public function testInvitationSignUp()
    {
        $attributes = $this->makeGenericInvitationAttributes();

        $invitation = $this->userRepository->invite($attributes);

        $this->assertInstanceOf(\App\Models\Collaboration\Invitation::class, $invitation);

        $invitationUrl = url("/api/auth/signup/{$invitation->invitation_token}");

        $response = $this->getJson($invitationUrl);

        $response->assertOk()
            ->assertExactJson([
                'email' => $attributes['email'],
                'role_name' => 'Administrator'
            ]);

        $password = $this->faker->password;

        $response = $this->postJson($invitationUrl, factory(User::class, 'registration')->raw());

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'first_name',
                'last_name',
                'email',
                'created_at',
                'activated_at'
            ]);
    }

    /**
     * Test Invitation Canceling and ensure that Invitation is not available from outbound.
     *
     * @return void
     */
    public function testInvitationCanceling()
    {
        $invitation = $this->userRepository->invite($this->makeGenericInvitationAttributes());

        $response = $this->putJson(url("api/invitations/cancel/{$invitation->invitation_token}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $invitation->refresh();

        $this->assertTrue($invitation->isExpired);

        $response = $this->getJson(url("/api/auth/signup/{$invitation->invitation_token}"));

        $response->assertNotFound()
            ->assertExactJson([
                'ErrorCode' => 'IE_01',
                'ErrorDetails' => IE_01,
                'message' => IE_01
            ]);
    }

    /**
     * Test Invitation Deleting and ensure that Invitation is not available from outbound.
     *
     * @return void
     */
    public function testInvitationDeleting()
    {
        $invitation = $this->userRepository->invite($this->makeGenericInvitationAttributes());

        $response = $this->deleteJson(
            url("api/invitations/{$invitation->invitation_token}")
        );

        $response->assertOk()
            ->assertExactJson([true]);

        $invitation->refresh();

        $this->assertNotNull($invitation->deleted_at);

        $response = $this->getJson(url("/api/auth/signup/{$invitation->invitation_token}"));

        $response->assertNotFound();
    }

    protected function makeGenericInvitationAttributes(): array
    {
        $role = $this->roleRepository->findByName('Administrator');

        return factory(Invitation::class)->raw([
            'role_id' => $role->id,
            'user_id' => $this->user->id
        ]);
    }
}
