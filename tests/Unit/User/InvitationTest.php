<?php

namespace Tests\Unit\User;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\{
    User,
    Collaboration\Invitation,
};
use App\Contracts\Repositories\{
    RoleRepositoryInterface as Roles,
    UserRepositoryInterface as Users,
};
use Illuminate\Support\{Arr, Str};

class InvitationTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    protected ?Roles $roleRepository = null;

    protected ?Users $userRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleRepository = app(Roles::class);
        $this->userRepository = app(Users::class);
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

        $this->getJson(url('api/invitations?' . $query))->assertOk();
    }

    /**
     * Invitation creating test with valid attributes.
     *
     * @return void
     */
    public function testInvitationCreating()
    {
        $attributes = $this->makeGenericInvitationAttributes();

        $this->postJson(url('api/users'), $attributes)
            ->assertOk()
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

        $this->assertInstanceOf(Invitation::class, $invitation);

        $invitationUrl = url("/api/auth/signup/{$invitation->invitation_token}");

        $this->getJson($invitationUrl)
            ->assertOk()
            ->assertExactJson([
                'email' => $attributes['email'],
                'role_name' => 'Administrator'
            ]);

        $user = factory(User::class)->raw();

        $attributes = array_merge($user, [
            'local_ip'              => $user['ip_address'],
            'password'              => 'password',
            'password_confirmation' => 'password',
            'g_recaptcha'           => Str::random(),
        ]);

        $this->postJson($invitationUrl, $attributes)
            ->assertOk()
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

        $this->putJson(url("api/invitations/cancel/{$invitation->invitation_token}"))
            ->assertOk()
            ->assertExactJson([true]);

        $invitation->refresh();

        $this->assertTrue($invitation->isExpired);

        $this->getJson(url("/api/auth/signup/{$invitation->invitation_token}"))
            ->assertNotFound()
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

        $this->deleteJson(url("api/invitations/{$invitation->invitation_token}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($invitation);

        $this->getJson(url("/api/auth/signup/{$invitation->invitation_token}"))->assertNotFound();
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
