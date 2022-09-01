<?php

namespace Tests\Feature;

use App\Enum\CompanyType;
use App\Models\{Collaboration\Invitation, Company, Role, SalesUnit, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Facades\Auth, Str};
use Tests\TestCase;
use function factory;
use const IE_01;

/**
 * @group build
 * @group user
 */
class InvitationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view listing of invitations.
     *
     * @return void
     */
    public function testCanViewListingOfInvitations()
    {
        $this->authenticateApi();

        $this->getJson('api/invitations')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'user_id', 'role_id', 'role_name', 'invitation_token', 'host', 'created_at', 'expires_at',
                        'is_expired',
                    ],
                ],
            ]);

        $this->getJson('api/invitations?'.http_build_query([
                'search' => Str::random(10), 'order_by_created_at' => 'asc', 'order_by_name' => 'desc',
            ]))
            ->assertOk();
    }

    /**
     * Test an ability to create a new invitation.
     *
     * @return void
     */
    public function testCanCreateNewInvitation(): void
    {
        $this->authenticateApi();

        $r = $this->postJson('api/users', $data = [
            'role_id' => factory(Role::class)->create()->getKey(),
            'host' => 'http://localhost',
            'email' => Str::random(40).'@email.com',
            'sales_units' => SalesUnit::factory(2)->create()->map->only('id'),
            'companies' => Company::factory(2)->create(['type' => CompanyType::INTERNAL])->map->only('id'),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'email',
                'host',
                'role_id',
                'user_id',
                'invitation_token',
                'role_name',
                'url',
                'sales_units' => [
                    '*' => ['id'],
                ],
                'companies' => [
                    '*' => ['id'],
                ],
                'expires_at',
                'created_at',
            ])
            ->assertJsonCount(count($data['sales_units']), 'sales_units')
            ->assertJsonCount(count($data['companies']), 'companies');

        foreach ($data['sales_units'] as $item) {
            $this->assertContains($item['id'], $r->json('sales_units.*.id'));
        }

        foreach ($data['companies'] as $item) {
            $this->assertContains($item['id'], $r->json('companies.*.id'));
        }
    }

    /**
     * Test an ability to register as a new user by the invitation.
     *
     * @return void
     */
    public function testCanRegisterAsUserUserByInvitation()
    {
        $this->authenticateApi();

        /** @var Invitation $invitation */
        $invitation = factory(Invitation::class)->create([
            'role_id' => factory(Role::class)->create()->getKey(),
        ]);

        $this->assertInstanceOf(Invitation::class, $invitation);

        $invitationUrl = "/api/auth/signup/$invitation->invitation_token";

        $this->getJson($invitationUrl)
            ->assertOk()
            ->assertExactJson([
                'email' => $invitation->email,
                'role_name' => $invitation->role->name,
            ]);

        $user = User::factory()->raw();

        $attributes = array_merge($user, [
            'local_ip' => $user['ip_address'],
            'password' => 'password',
            'password_confirmation' => 'password',
            'g_recaptcha' => Str::random(),
        ]);

        $this->postJson($invitationUrl, $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'first_name',
                'last_name',
                'email',
                'created_at',
                'activated_at',
            ]);

        Auth::shouldUse('web');

        $this->postJson('api/auth/signin', [
            'email' => $invitation->email,
            'password' => 'password',
            'g_recaptcha' => Str::random(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_at',
                'recaptcha_response',
            ]);
    }

    /**
     * Test an ability to cancel an existing invitation.
     *
     * @return void
     */
    public function testCanCancelInvitation()
    {
        $this->authenticateApi();

        $invitation = factory(Invitation::class)->create([
            'role_id' => factory(Role::class)->create()->getKey(),
        ]);

        $this->putJson("api/invitations/cancel/$invitation->invitation_token")
            ->assertOk()
            ->assertExactJson([true]);

        $invitation->refresh();

        $this->assertTrue($invitation->isExpired);

        $this->getJson("/api/auth/signup/$invitation->invitation_token")
            ->assertNotFound()
            ->assertExactJson([
                'ErrorCode' => 'IE_01',
                'ErrorDetails' => IE_01,
                'message' => IE_01,
            ]);
    }

    /**
     * Test an ability to delete an existing invitation.
     *
     * @return void
     */
    public function testCanDeleteInvitation()
    {
        $this->authenticateApi();

        $invitation = factory(Invitation::class)->create([
            'role_id' => factory(Role::class)->create()->getKey(),
        ]);

        $this->deleteJson("api/invitations/$invitation->invitation_token")
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($invitation);

        $this->getJson("/api/auth/signup/$invitation->invitation_token")->assertNotFound();
    }
}
