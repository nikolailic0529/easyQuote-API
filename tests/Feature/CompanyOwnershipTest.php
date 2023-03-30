<?php

namespace Tests\Feature;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Models\Note;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Enum\OpportunityStatus;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 * @group company
 * @group ownership
 */
class CompanyOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to change ownership of an existing company
     * when acting as super administrator.
     */
    public function testCanChangeOwnershipOfCompanyAsSuperAdministrator(): void
    {
        $this->authenticateApi();

        /** @var Company $c */
        $c = Company::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->create();
        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/companies/'.$c->getKey())
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
     * Test an ability to change ownership of an existing company
     * when user has enough permissions.
     */
    public function testCanChangeOwnershipOfCompanyWhenHasPermissionTo(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_companies',
            'update_companies',
            'delete_companies',
            'change_companies_ownership',
        ]);
        /** @var User $causer */
        $causer = User::factory()->hasAttached(SalesUnit::factory())->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Company $c */
        $c = Company::factory()
            ->for($causer, 'owner')
            ->for($causer->salesUnits->sole(), 'salesUnit')
            ->create();
        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/companies/'.$c->getKey())
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
     * Test an ability to change ownership of an existing company
     * when user doesn't have enough permissions.
     */
    public function testCanNotChangeOwnershipOfCompanyWhenDoesntHavePermission(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_companies',
            'update_companies',
            'delete_companies',
        ]);
        /** @var User $causer */
        $causer = User::factory()->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Company $c */
        $c = Company::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->create();
        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing system defined company.
     */
    public function testCanNotChangeOwnershipOfSystemCompany(): void
    {
        $this->authenticateApi();

        /** @var Company $c */
        $c = Company::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->create(['flags' => Company::SYSTEM]);

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
        ])
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing company & its linked records.
     */
    public function testCanChangeOwnershipOfCompanyLinkedRecords(): void
    {
        $this->authenticateApi();

        $anotherOwner = User::factory()->create();

        /** @var Company $c */
        $c = Company::factory()
            ->for(User::factory(), 'owner')
            ->for(SalesUnit::factory(), 'salesUnit')
            ->has(Opportunity::factory(), 'opportunities')
            ->has(Opportunity::factory(), 'opportunitiesWhereEndUser')
            ->hasAttached(Address::factory(2))
            ->hasAttached(Contact::factory(2))
            ->hasAttached(Note::factory(2))
            ->hasAttached(Task::factory(2))
            ->hasAttached(Appointment::factory(2), relationship: 'ownAppointments')
            ->create();

        $lostOpps = Collection::empty();
        $lostOpps->push(
            Opportunity::factory()
                ->state(['status' => OpportunityStatus::LOST])
                ->for($anotherOwner)
                ->for($c, 'primaryAccount')
                ->create()
        );
        $lostOpps->push(
            Opportunity::factory()
                ->state(['status' => OpportunityStatus::LOST])
                ->for($anotherOwner)
                ->for($c, 'endUser')
                ->create()
        );

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
            'transfer_linked_records_to_new_owner' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $r = $this->getJson('api/companies/'.$c->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sales_unit_id',
                'addresses' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'user_id',
                        'sales_unit_id',
                    ],
                ],
            ])
            ->assertJsonPath('user_id', $data['owner_id'])
            ->assertJsonPath('sales_unit_id', $data['sales_unit_id'])
            ->assertJsonCount(2, 'addresses')
            ->assertJsonCount(2, 'contacts');

        foreach ($r->json('addresses.*.user_id') as $userId) {
            $this->assertSame($newOwner->getKey(), $userId);
        }

        foreach ($r->json('contacts') as ['user_id' => $userId, 'sales_unit_id' => $unitId]) {
            $this->assertSame($newOwner->getKey(), $userId);
            $this->assertSame($newUnit->getKey(), $unitId);
        }

        $r = $this->getJson('api/companies/'.$c->getKey().'/notes')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'owner_user_id',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        foreach ($r->json('data.*.owner_user_id') as $userId) {
            $this->assertSame($newOwner->getKey(), $userId);
        }

        $r = $this->getJson('api/tasks/taskable/'.$c->getKey())
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

        $r = $this->getJson('api/companies/'.$c->getKey().'/appointments')
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

        $r = $this->getJson('api/companies/'.$c->getKey().'/opportunities')
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
            ->assertJsonCount(4, 'data');

        foreach ($r->json('data') as ['id' => $id, 'user_id' => $userId, 'sales_unit_id' => $unitId]) {
            if ($lostOpps->containsStrict('id', $id)) {
                $this->assertSame($anotherOwner->getKey(), $userId);
                continue;
            }

            $this->assertSame($newOwner->getKey(), $userId);
            $this->assertSame($newUnit->getKey(), $unitId);
        }
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
            'view_companies',
            'create_companies', 'update_companies', 'delete_companies',
        );
        $originalOwner->syncRoles($originalOwnerRole);

        /** @var Company $c */
        $c = Company::factory()
            ->for($originalOwner, 'owner')
            ->create();

        $newOwner = User::factory()->create();
        $newUnit = SalesUnit::factory()->create();

        $originalOwner->salesUnits()->attach($newUnit);

        $this->patchJson('api/companies/'.$c->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'sales_unit_id' => $newUnit->getKey(),
            'transfer_linked_records_to_new_owner' => false,
            'keep_original_owner_as_editor' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/companies/'.$c->getKey())
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

        $this->getJson('api/companies/'.$c->getKey())
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
            ->assertJsonPath('permissions.delete', true);
    }
}
