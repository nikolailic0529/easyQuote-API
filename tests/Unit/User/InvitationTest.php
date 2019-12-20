<?php

namespace Tests\Unit\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Tests\Unit\Traits\AssertsListing;
use Arr;

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
    }

    /**
     * Invitation creating test.
     *
     * @return void
     */
    public function testInvitationCreating()
    {
        $attributes = $this->makeGenericInvitationAttributes();

        $response = $this->postJson(
            url('api/users'),
            $attributes,
            $this->authorizationHeader
        );

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'email', 'host', 'role_id', 'user_id', 'invitation_token', 'expires_at', 'created_at', 'role_name', 'url'
            ]);

        /**
         * Checking that Invitation was added in the listing.
         */
        $response = $this->getJson(url('api/invitations'), $this->authorizationHeader);

        $response->assertJsonFragment(
            Arr::only($attributes, ['email', 'role_id']) + ['is_expired' => false]
        );
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

        $response = $this->postJson($invitationUrl, [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'password' => $password,
            'password_confirmation' => $password,
            'timezone_id' => app('timezone.repository')->random()->id
        ]);

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
     * Test Invitation Canceling and no availability when trying to use a given invitation.
     *
     * @return void
     */
    public function testInvitationCanceling()
    {
        $invitation = $this->userRepository->invite($this->makeGenericInvitationAttributes());

        $response = $this->putJson(
            url("api/invitations/cancel/{$invitation->invitation_token}"),
            [],
            $this->authorizationHeader
        );

        $response->assertOk()
            ->assertExactJson([true]);

        $invitation->refresh();

        $this->assertTrue($invitation->isExpired);

        $response = $this->getJson(url("/api/auth/signup/{$invitation->invitation_token}"));

        $response->assertNotFound()
            ->assertExactJson([
                'message' => IE_01,
                'error_code' => 'IE_01'
            ]);
    }

    /**
     * Test Invitation Deleting and no availability when trying to use a given invitation.
     *
     * @return void
     */
    public function testInvitationDeleting()
    {
        $invitation = $this->userRepository->invite($this->makeGenericInvitationAttributes());

        $response = $this->deleteJson(
            url("api/invitations/{$invitation->invitation_token}"),
            [],
            $this->authorizationHeader
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

        return ['email' => $this->faker->safeEmail, 'role_id' => $role->id, 'host' => config('app.url')];
    }
}
