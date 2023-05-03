<?php

namespace Tests\Feature;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Models\Note;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 * @group opportunity
 * @group ownership
 */
class OpportunityOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to change ownership of an existing opportunity
     * when acting as super administrator.
     */
    public function testCanChangeOwnershipOfOpportunityAsSuperAdministrator(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->create();
        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opp->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
            ])
            ->assertJsonPath('user_id', $data['owner_id'])
            ->assertJsonPath('sales_unit_id', $data['sales_unit_id']);
    }

    /**
     * Test an ability to change ownership of an existing opportunity
     * when user has enough permissions.
     */
    public function testCanChangeOwnershipOfOpportunityWhenHasPermissionTo(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_opportunities',
            'create_opportunities',
            'update_own_opportunities',
            'delete_own_opportunities',
            'change_opportunities_ownership',
        ]);
        /** @var User $causer */
        $causer = User::factory()->hasAttached(SalesUnit::factory())->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for($causer, 'owner')
            ->for($causer->salesUnits->first(), 'salesUnit')
            ->create();

        $newOwner = User::factory()->create();
        $this->getJson('api/opportunities/'.$opp->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
                'permissions' => [
                    'view',
                    'update',
                    'delete',
                ],
            ])
            ->assertJsonPath('user_id', $opp->owner->getKey())
            ->assertJsonPath('sales_unit_id', $opp->salesUnit->getKey())
            ->assertJsonPath('permissions.view', true)
            ->assertJsonPath('permissions.update', true)
            ->assertJsonPath('permissions.delete', true)
            ->assertJsonPath('permissions.change_ownership', true);
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opp->getKey())
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing opportunity
     * when user doesn't have enough permissions.
     */
    public function testCanNotChangeOwnershipOfOpportunityWhenDoesntHavePermission(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_opportunities',
            'create_opportunities',
            'update_own_opportunities',
            'delete_own_opportunities',
        ]);
        /** @var User $causer */
        $causer = User::factory()->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->create();
        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing opportunity & its linked records.
     */
    public function testCanChangeOwnershipOfOpportunityLinkedRecords(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for(Company::factory(), 'primaryAccount')
            ->for(Company::factory(), 'endUser')
            ->for(Contact::factory(), 'primaryAccountContact')
            ->hasAttached(Note::factory(2))
            ->hasAttached(Task::factory(2))
            ->hasAttached(Appointment::factory(2), relationship: 'ownAppointments')
            ->hasAttached(Attachment::factory(2), relationship: 'attachments')
            ->create();

        // Another opportunity having the same linked account.
        /** @var Opportunity $opp2 */
        $opp2 = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for($opp->primaryAccount, 'primaryAccount')
            ->create();

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $opp2UpdatedAt = $this->getJson('api/opportunities/'.$opp2->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ])
            ->json('updated_at');
        $this->assertNotEmpty($opp2UpdatedAt);

        $this->travelTo(now()->addDay());

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
            'transfer_linked_records_to_new_owner' => true,
        ])
//            ->dump()
            ->assertNoContent();

        /**
         * We have to ensure the other opportunities which are linked via account relation
         * were touched.
         * This is required for sync consistency.
         */
        $opp2UpdatedAtAfter = $this->getJson('api/opportunities/'.$opp2->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ])
            ->json('updated_at');
        $this->assertNotEquals($opp2UpdatedAt, $opp2UpdatedAtAfter);

        $r = $this->getJson('api/opportunities/'.$opp->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
                'primary_account' => [
                    'id',
                    'user_id',
                ],
                'end_user' => [
                    'id',
                    'user_id',
                ],
            ])
            ->assertJsonPath('user_id', $data['owner_id'])
            ->assertJsonPath('sales_unit_id', $data['sales_unit_id'])
            ->assertJsonPath('primary_account.user_id', $data['owner_id'])
            ->assertJsonPath('primary_account.sales_unit_id', $data['sales_unit_id'])
            ->assertJsonPath('end_user.user_id', $data['owner_id'])
            ->assertJsonPath('end_user.sales_unit_id', $data['sales_unit_id']);

        $r = $this->getJson('api/opportunities/'.$opp->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        foreach ($r->json('data.*.user_id') as $userId) {
            $this->assertSame($newOwner->getKey(), $userId);
        }

        $r = $this->getJson('api/tasks/taskable/'.$opp->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'sales_unit_id',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        foreach ($r->json('data') as ['user_id' => $userId, 'sales_unit_id' => $unitId]) {
            $this->assertSame($newOwner->getKey(), $userId);
            $this->assertSame($newUnit->getKey(), $unitId);
        }

        $r = $this->getJson('api/opportunities/'.$opp->getKey().'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'sales_unit_id',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        foreach ($r->json('data') as ['user_id' => $userId, 'sales_unit_id' => $unitId]) {
            $this->assertSame($newOwner->getKey(), $userId);
            $this->assertSame($newUnit->getKey(), $unitId);
        }

        $r = $this->getJson('api/opportunities/'.$opp->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        foreach ($r->json('data') as ['user_id' => $userId]) {
            $this->assertSame($newOwner->getKey(), $userId);
            $this->assertSame($newUnit->getKey(), $unitId);
        }
    }

    public function testCanChangeOwnershipOfOpportunityAttachedQuotes(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for(Company::factory()->for(User::factory()), 'primaryAccount')
            ->for(Company::factory()->for(User::factory()), 'endUser')
            ->for(Contact::factory(), 'primaryAccountContact')
            ->has(WorldwideQuote::factory())
            ->create();

        $opp->primaryAccount->load('owner');
        $opp->endUser->load('owner');

        // Another opportunity having the same linked account.
        /** @var Opportunity $opp2 */
        $opp2 = Opportunity::factory()
            ->for(User::factory(), 'owner')
            ->for($opp->primaryAccount, 'primaryAccount')
            ->create();

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $opp2UpdatedAt = $this->getJson('api/opportunities/'.$opp2->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ])
            ->json('updated_at');
        $this->assertNotEmpty($opp2UpdatedAt);

        $this->travelTo(now()->addDay());

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
            'transfer_attached_quote_to_new_owner' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $opp2UpdatedAtAfter = $this->getJson('api/opportunities/'.$opp2->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ])
            ->json('updated_at');
        $this->assertEquals($opp2UpdatedAt, $opp2UpdatedAtAfter);

        $r = $this->getJson('api/opportunities/'.$opp->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
                'primary_account' => [
                    'id',
                    'user_id',
                ],
                'end_user' => [
                    'id',
                    'user_id',
                ],
                'quotes_exist',
                'quote' => [
                    'id',
                    'user' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonPath('user_id', $data['owner_id'])
            ->assertJsonPath('sales_unit_id', $data['sales_unit_id'])
            ->assertJsonPath('quotes_exist', true)
            ->assertJsonPath('quote.user.id', $data['owner_id'])
            ->assertJsonPath('primary_account.user_id', $opp->primaryAccount->owner->getKey())
            ->assertJsonPath('primary_account.sales_unit_id', $opp->primaryAccount->salesUnit->getKey())
            ->assertJsonPath('end_user.user_id', $opp->endUser->owner->getKey())
            ->assertJsonPath('end_user.sales_unit_id', $opp->endUser->salesUnit->getKey());
    }

    /**
     * Test an ability to keep original owner as editor when changing company ownership.
     */
    public function testCanKeepOriginalOwnerAsEditorWhenChangingCompanyOwnership(): void
    {
        $this->authenticateApi();

        /** @var User $originalOwner */
        $originalOwner = User::factory()->create();
        /** @var Role $originalOwnerRole */
        $originalOwnerRole = Role::factory()->create();
        $originalOwnerRole->syncPermissions(
            'view_opportunities',
            'create_opportunities',
            'update_own_opportunities',
            'delete_own_opportunities',
            'view_opportunities_where_editor',
            'update_opportunities_where_editor',
        );
        $originalOwner->syncRoles($originalOwnerRole);

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()
            ->for($originalOwner, 'owner')
            ->create();

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $originalOwner->salesUnits()->attach($newUnit);

        $this->patchJson('api/opportunities/'.$opp->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
            'transfer_linked_records_to_new_owner' => false,
            'keep_original_owner_as_editor' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opp->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
                'sharing_users' => [
                    '*' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'sharing_users')
            ->assertJsonPath('sharing_users.0.id', $originalOwner->getKey());

        $this->actingAs($originalOwner, 'api');

        $this->getJson('api/opportunities/'.$opp->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'permissions' => [
                    'view',
                    'update',
                    'delete',
                ],
            ])
            ->assertJsonPath('permissions.view', true)
            ->assertJsonPath('permissions.update', true)
            ->assertJsonPath('permissions.delete', false);

        $this->getJson('api/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $opp->getKey());
    }
}
