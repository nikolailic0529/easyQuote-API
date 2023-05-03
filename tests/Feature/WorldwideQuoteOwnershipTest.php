<?php

namespace Tests\Feature;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authorization\Models\Role;
use App\Domain\Note\Models\Note;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 * @group worldwide-quote
 * @group ownership
 */
class WorldwideQuoteOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to change ownership of an existing worldwide quote
     * when acting as super administrator.
     */
    public function testCanChangeOwnershipOfWorldwideQuoteAsSuperAdministrator(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for(User::factory(), 'user')
            ->create();

        $newOwner = User::factory()->create();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
            ])
            ->assertJsonPath('user_id', $data['owner_id']);
    }

    /**
     * Test an ability to change ownership of an existing worldwide quote
     * when user has enough permissions.
     */
    public function testCanChangeOwnershipOfWorldwideQuoteWhenHasPermissionTo(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_own_ww_quotes',
            'create_ww_quotes',
            'update_own_ww_quotes',
            'delete_own_ww_quotes',
            'change_ww_quotes_ownership',
        ]);
        /** @var User $causer */
        $causer = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        $opp = Opportunity::factory()
            ->for($causer->salesUnits->first())
            ->create();

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for($causer, 'user')
            ->for($opp)
            ->create();

        $newOwner = User::factory()->create();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
            ])
            ->assertJsonPath('user_id', $causer->getKey());

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to change ownership of an existing worldwide quote
     * when user doesn't have enough permissions.
     */
    public function testCanNotChangeOwnershipOfWorldwideQuoteWhenDoesntHavePermission(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();
        $role->syncPermissions([
            'view_own_ww_quotes',
            'create_ww_quotes',
            'update_own_ww_quotes',
            'delete_own_ww_quotes',
        ]);
        /** @var User $causer */
        $causer = User::factory()->create();
        $causer->syncRoles($role);

        $this->authenticateApi($causer);

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for(User::factory(), 'user')
            ->create();
        $newOwner = User::factory()->create();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
        ])
//            ->dump()
            ->assertForbidden();
    }

    public function testCanChangeOwnershipOfWorldwideQuoteLinkedRecords(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for(User::factory(), 'user')
            ->for(
                Opportunity::factory()
                    ->hasAttached(Note::factory(2))
                    ->hasAttached(Task::factory(2))
                    ->hasAttached(Appointment::factory(2), relationship: 'ownAppointments')
                    ->hasAttached(Attachment::factory(2), relationship: 'attachments')
            )
            ->create();
        $newOwner = User::factory()->create();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'transfer_linked_records_to_new_owner' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $r = $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
            ])
            ->assertJsonPath('user_id', $data['owner_id']);

        $r = $this->getJson('api/opportunities/'.$quote->opportunity()->getParentKey().'/notes')
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

        $r = $this->getJson('api/tasks/taskable/'.$quote->opportunity()->getParentKey())
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

        foreach ($r->json('data') as ['user_id' => $userId]) {
            $this->assertSame($newOwner->getKey(), $userId);
        }

        $r = $this->getJson('api/opportunities/'.$quote->opportunity()->getParentKey().'/appointments')
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

        foreach ($r->json('data') as ['user_id' => $userId]) {
            $this->assertSame($newOwner->getKey(), $userId);
        }

        $r = $this->getJson('api/opportunities/'.$quote->opportunity()->getParentKey().'/attachments')
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
        }
    }

    /**
     * Test an ability to keep original owner as editor when changing worldwide quote ownership.
     */
    public function testCanKeepOriginalOwnerAsEditorWhenChangingWorldwideQuoteOwnership(): void
    {
        $this->authenticateApi();

        /** @var User $originalOwner */
        $originalOwner = User::factory()->create();
        /** @var Role $originalOwnerRole */
        $originalOwnerRole = Role::factory()->create();
        $originalOwnerRole->syncPermissions(
            'view_own_ww_quotes', 'view_own_ww_quote_files',
            'create_ww_quotes', 'update_own_ww_quotes',
            'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',
            'delete_own_ww_quotes', 'delete_own_ww_quote_files',
            'view_ww_quotes_where_editor',
            'update_ww_quotes_where_editor',
        );
        $originalOwner->syncRoles($originalOwnerRole);

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for($originalOwner, 'user')
            ->create();

        $originalOwner->salesUnits()->attach($quote->salesUnit);

        $newOwner = User::factory()->create();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'transfer_linked_records_to_new_owner' => false,
            'keep_original_owner_as_editor' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=sharing_users')
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

        $this->actingAs($originalOwner, 'api');

        $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=sharing_users')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'permissions' => [
                    'view',
                    'update',
                    'delete',
                    'change_ownership',
                ],
            ])
            ->assertJsonPath('permissions.view', true)
            ->assertJsonPath('permissions.update', true)
            ->assertJsonPath('permissions.delete', false)
            ->assertJsonPath('permissions.change_ownership', false);
    }

    /**
     * Test an ability to change ownership of worldwide quote and a selected version.
     */
    public function testCanChangeOwnershipOfWorldwideQuoteAndVersion(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for(User::factory(), 'user')
            ->create();

        $ver = WorldwideQuoteVersion::factory()
            ->for(User::factory())
            ->for($quote)
            ->create();

        $newOwner = User::factory()->create();

        $ownedVer = WorldwideQuoteVersion::factory()
            ->for($newOwner)
            ->for($quote)
            ->create();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/ownership', $data = [
            'owner_id' => $newOwner->getKey(),
            'transfer_linked_records_to_new_owner' => false,
            'keep_original_owner_as_editor' => false,
            'version_ownership' => true,
            'version_id' => $ver->getKey(),
        ])
//            ->dump()
            ->assertNoContent();

        $r = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include=versions')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'versions' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ])
            ->assertJsonPath('user_id', $newOwner->getKey())
            ->assertJsonCount(3, 'versions');

        $this->assertContains($ver->getKey(), $r->json('versions.*.id'));

        $verMap = collect($r->json('versions'))->keyBy('id');

        $this->assertArrayHasKey($ver->getKey(), $verMap->all());
        $this->assertArrayHasKey($ownedVer->getKey(), $verMap->all());

        foreach ($verMap->only([$ver->getKey(), $ownedVer->getKey()]) as $version) {
            $this->assertSame($newOwner->getKey(), $version['user_id']);
        }

        foreach ($verMap->except([$ver->getKey(), $ownedVer->getKey()]) as $version) {
            $this->assertNotSame($newOwner->getKey(), $version['user_id']);
        }

        $this->assertSame(0, $verMap[$ownedVer->getKey()]['user_version_sequence_number']);
        $this->assertSame(1, $verMap[$ver->getKey()]['user_version_sequence_number']);
    }
}
