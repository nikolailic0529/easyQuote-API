<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class AssetTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view assets listing.
     *
     * @return void
     */
    public function testCanViewAssetsListing()
    {
        $this->authenticateApi();

        factory(Asset::class, 20)->create();

        $worldwideQuote = factory(WorldwideQuote::class)->create();

        factory(Asset::class)->create([
            'quote_id' => $worldwideQuote->getKey(),
            'quote_type' => $worldwideQuote->getMorphClass(),
        ]);

        $this->getJson('api/assets?order_by_created_at=desc')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'asset_category_id',
                        'user_id',
                        'address_id',
                        'vendor_id',
                        'quote_id',
                        'customer_name',
                        'rfq_number',
                        'vendor_short_code',
                        'asset_category_name',
                        'unit_price',
                        'base_warranty_start_date',
                        'base_warranty_end_date',
                        'active_warranty_start_date',
                        'active_warranty_end_date',
                        'product_number',
                        'serial_number',
                        'product_description',
                        'product_image',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->getJson('api/assets?order_by_customer_name=asc')->assertOk();
    }

    /**
     * Test an ability to view paginated assets of business division of user's team.
     *
     * @return void
     */
    public function testCanViewPaginatedAssetsOfBusinessDivisionOfUserTeam()
    {
        $this->authenticateApi();

        /** @var Quote $rescueQuote */
        $rescueQuote = factory(Quote::class)->create();

        $team = factory(Team::class)->create([
            'business_division_id' => BD_RESCUE,
        ]);

        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_assets');

        /** @var User $user */
        $user = User::factory()->create([
            'team_id' => $team->getKey(),
        ]);

        $user->syncRoles($role);

        $this->be($user, 'api');

        /** @var Asset $asset */
        $asset = factory(Asset::class)->create([
            'user_id' => $user->getKey(),
            'quote_id' => $rescueQuote->getKey(),
            'quote_type' => $rescueQuote->getMorphClass(),
        ]);

        $response = $this->getJson('api/assets')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'asset_category_id',
                        'user_id',
                        'address_id',
                        'vendor_id',
                        'quote_id',
                        'rfq_number',
                        'vendor_short_code',
                        'asset_category_name',
                        'unit_price',
                        'base_warranty_start_date',
                        'base_warranty_end_date',
                        'active_warranty_start_date',
                        'active_warranty_end_date',
                        'product_number',
                        'serial_number',
                        'product_description',
                        'product_image',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(1, $response->json('data'));
        $this->assertContains($asset->getKey(), $response->json('data.*.id'));
        $this->assertContains($rescueQuote->customer->rfq, $response->json('data.*.rfq_number'));
    }

    /**
     * Test can create a new asset.
     *
     * @return void
     */
    public function testCanCreateNewAsset()
    {
        $this->authenticateApi();

        $attributes = factory(Asset::class)->raw();

        $this->getJson('api/assets/create')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'asset_categories' => [
                    '*' => [
                        'id', 'name'
                    ]
                ],
                'vendors' => [
                    '*' => [
                        'id', 'name',
                    ]
                ]
            ]);

        $response = $this->postJson('api/assets', $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'asset_category_id',
                'user_id',
                'address_id',
                'vendor_id',
                'quote_id',
                'rfq_number',
                'vendor_short_code',
                'asset_category_name',
                'unit_price',
                'base_warranty_end_date',
                'base_warranty_start_date',
                'active_warranty_end_date',
                'active_warranty_start_date',
                'product_number',
                'serial_number',
                'product_description',
                'product_image',
                'created_at',
                'updated_at',
            ]);

        $modelKey = $response->json('id');

        $this->getJson("api/assets/$modelKey")->assertOk();
    }

    /**
     * Test can update a newly created asset.
     *
     * @return void
     */
    public function testCanUpdateAsset()
    {
        $this->authenticateApi();

        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $attributes = factory(Asset::class)->raw();

        $response = $this->patchJson('api/assets/'.$asset->getKey(), $attributes)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'asset_category_id',
                'user_id',
                'address_id',
                'vendor_id',
                'quote_id',
                'rfq_number',
                'vendor_short_code',
                'asset_category_name',
                'unit_price',
                'base_warranty_end_date',
                'base_warranty_start_date',
                'active_warranty_end_date',
                'active_warranty_start_date',
                'product_number',
                'serial_number',
                'product_description',
                'product_image',
                'created_at',
                'updated_at',
            ]);

        $modelKey = $response->json('id');

        $this->getJson("api/assets/$modelKey")->assertOk();
    }

    /**
     * Test can delete a newly created asset.
     *
     * @return void
     */
    public function testCanDeleteAsset()
    {
        $this->authenticateApi();

        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $this->deleteJson('api/assets/'.$asset->getKey())->assertOk();

        $this->getJson("api/assets/{$asset->getKey()}")->assertNotFound();
    }

    /**
     * Test an ability to check uniqueness of the specified asset data.
     *
     * @return void
     */
    public function testCanCheckUniquenessOfAsset()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/assets/unique', [
            'vendor_id' => Vendor::query()->where('short_code', 'HPE')->value('id'),
            'serial_number' => Str::random(20),
            'product_number' => Str::random(20),
        ])
//            ->dump()
            ->assertOk();

        $this->assertTrue(filter_var($response->getContent(), FILTER_VALIDATE_BOOLEAN));

        /** @var Asset $asset */
        $asset = factory(Asset::class)->create();

        $response = $this->postJson('api/assets/unique', [
            'vendor_id' => $asset->vendor_id,
            'serial_number' => $asset->serial_number,
            'product_number' => $asset->product_number,
            'user_id' => $asset->user()->getParentKey(),
        ])
//            ->dump()
            ->assertOk();

        $this->assertFalse(filter_var($response->getContent(), FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Test an ability to view existing companies list of the specified asset entity.
     *
     * @return void
     */
    public function testCanViewCompaniesOfAsset()
    {
        $this->authenticateApi();

        /** @var Asset $asset */
        $asset = factory(Asset::class)->create();
        /** @var Company $companyOfAsset */
        $companyOfAsset = Company::factory()->create();
        /** @var Company $anotherCompany */
        $anotherCompany = Company::factory()->create();

        $companyOfAsset->assets()->sync($asset);

        $response = $this->getJson('api/assets/'.$asset->getKey().'/companies')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'source',
                        'type',
                        'email',
                        'phone',
                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                        'created_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(1, $response->json('data.*.id'));
        $this->assertContains($companyOfAsset->getKey(), $response->json('data.*.id'));
    }
}
