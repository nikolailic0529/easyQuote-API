<?php

namespace Tests\Feature;

use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 * @group asset
 * @group ownership
 */
class AssetOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to change ownership of an existing asset
     * when acting as super administrator.
     */
    public function testCanChangeOwnershipOfAssetAsSuperAdministrator(): void
    {
        $this->authenticateApi();

        /** @var Company $asset */
        $asset = Asset::factory()
            ->for(User::factory(), 'user')
            ->create();
        $newOwner = User::factory()->create();

        $this->patchJson('api/assets/'.$asset->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/assets/'.$asset->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
            ])
            ->assertJsonPath('user_id', $data['owner_id']);
    }

    /**
     * Test an ability to change ownership of an existing asset
     * when user has enough permissions.
     */
    public function testCanChangeOwnershipOfAssetWhenHasPermissionTo(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_assets',
            'update_assets',
            'delete_assets',
            'change_assets_ownership',
        ]);
        /** @var User $causer */
        $causer = User::factory()->hasAttached(SalesUnit::factory())->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Asset $asset */
        $asset = Asset::factory()
            ->for(User::factory(), 'user')
            ->create();
        $newOwner = User::factory()->create();

        $this->patchJson('api/assets/'.$asset->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/assets/'.$asset->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
            ])
            ->assertJsonPath('user_id', $data['owner_id']);
    }

    /**
     * Test an ability to change ownership of an existing asset
     * when user doesn't have enough permissions.
     */
    public function testCanNotChangeOwnershipOfAssetWhenDoesntHavePermission(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_assets',
            'update_assets',
            'delete_assets',
        ]);
        /** @var User $causer */
        $causer = User::factory()->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var Asset $asset */
        $asset = Asset::factory()
            ->for(User::factory(), 'user')
            ->create();
        $newOwner = User::factory()->create();

        $this->patchJson('api/assets/'.$asset->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing asset & its linked records.
     */
    public function testCanChangeOwnershipOfAssetLinkedRecords(): void
    {
        $this->authenticateApi();

        $anotherOwner = User::factory()->create();

        /** @var Asset $asset */
        $asset = Asset::factory()
            ->for(User::factory(), 'user')
            ->for(Address::factory())
            ->create();

        $newOwner = User::factory()->create();

        $this->patchJson('api/assets/'.$asset->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'transfer_linked_records_to_new_owner' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $r = $this->getJson('api/assets/'.$asset->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'address' => [
                    'id',
                    'user_id',
                ],
            ])
            ->assertJsonPath('user_id', $data['owner_id'])
            ->assertJsonPath('address.user_id', $data['owner_id']);
    }

    /**
     * Test an ability to keep original owner as editor when changing asset ownership.
     */
    public function testCanKeepOriginalOwnerAsEditorWhenChangingAssetOwnership(): void
    {
        $this->authenticateApi();

        /** @var Asset $asset */
        $asset = Asset::factory()
            ->for(User::factory(), 'user')
            ->create();
        $originalOwner = $asset->user;

        $newOwner = User::factory()->create();

        $this->patchJson('api/assets/'.$asset->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'transfer_linked_records_to_new_owner' => false,
            'keep_original_owner_as_editor' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/assets/'.$asset->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'sharing_users' => [
                    '*' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'sharing_users')
            ->assertJsonPath('sharing_users.0.id', $originalOwner->getKey());
    }
}
