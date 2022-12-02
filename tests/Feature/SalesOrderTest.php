<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesUnit;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateField;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Services\SalesOrder\CancelSalesOrderService;
use App\Services\SalesOrder\SubmitSalesOrderService;
use App\Services\VendorServices\CheckSalesOrderService;
use App\Services\VendorServices\OauthClient as VSOauthClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class SalesOrderTest
 *
 * @group worldwide
 */
class SalesOrderTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test an ability to view drafted sales orders listing.
     *
     * @return void
     */
    public function testCanViewDraftedSalesOrdersListing(): void
    {
        $this->authenticateApi();

        /** @var SalesOrder[] $orders */
        $orders = factory(SalesOrder::class, 10)->create(['submitted_at' => null, 'assets_count' => 3]);

        $endUser = Company::factory()->create();
        $accountMgr = User::factory()->create();

        foreach ($orders as $order) {
            $order->worldwideQuote->opportunity->endUser()->associate($endUser);
            $order->worldwideQuote->opportunity->accountManager()->associate($accountMgr)->save();
        }

        $this->getJson('api/sales-orders/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'contract_type_id',
                        'worldwide_quote_id',
                        'order_number',
                        'rfq_number',
                        'customer_name',
                        'company_name',
                        'end_user_name',
                        'account_manager_name',
                        'account_manager_email',
                        'assets_count',
//                        'sequence_number',
                        'order_type',
                        'opportunity_name',
                        'created_at',
                        'activated_at',
                    ],
                ],
                'links' => [
                    'first', 'last', 'prev', 'next',
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total',
                ],
            ]);
    }

    /**
     * Test an ability to view only own paginated sales orders.
     */
    public function testCanViewOwnPaginatedDraftedSalesOrders(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_sales_orders');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        /** @var SalesOrder $salesOrder */
        // Acting user own entity.
        $salesOrder = SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for($user->salesUnits->first())
                    )
            )
            ->create([
                'submitted_at' => null,
            ]);

        // Entity own by different user.
        SalesOrder::factory()
            ->for($anotherUser = User::factory())
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for(SalesUnit::factory())
                    )
            )
            ->create([
                'submitted_at' => null,
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/sales-orders/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
    }

    /**
     * Test an ability to view only drafted sales orders belong to the assigned sales units to user.
     */
    public function testCanViewDraftedSalesOrdersBelongToAssignedSalesUnitsToUser(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();

        $role->syncPermissions('view_own_sales_orders');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        /** @var SalesOrder $salesOrder */
        // Acting user own entity.
        $salesOrder = SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for($user->salesUnits->first())
                    )
            )
            ->create([
                'submitted_at' => null,
            ]);

        // Owned but different sales unit.
        SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for(SalesUnit::factory())
                    )
            )
            ->create([
                'submitted_at' => null,
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/sales-orders/drafted')
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
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
        $this->assertSame($user->salesUnits->first()->getKey(), $response->json('data.0.sales_unit_id'));
    }

    /**
     * Test an ability to view only submitted sales orders belong to the assigned sales units to user.
     */
    public function testCanViewSubmittedSalesOrdersBelongToAssignedSalesUnitsToUser(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();

        $role->syncPermissions('view_own_sales_orders');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        /** @var SalesOrder $salesOrder */
        // Acting user own entity.
        $salesOrder = SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for($user->salesUnits->first())
                    )
            )
            ->create([
                'submitted_at' => now(),
            ]);

        // Owned but different sales unit.
        SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for(SalesUnit::factory())
                    )
            )
            ->create([
                'submitted_at' => now(),
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/sales-orders/submitted')
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
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
        $this->assertSame($user->salesUnits->first()->getKey(), $response->json('data.0.sales_unit_id'));
    }

    /**
     * Test an ability to view only own paginated sales orders.
     */
    public function testCanViewOwnPaginatedSubmittedSalesOrders(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_sales_orders');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        /** @var SalesOrder $salesOrder */
        // Acting user own entity.
        $salesOrder = SalesOrder::factory()
            ->for($user)
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for($user->salesUnits->first())
                    )
            )
            ->create([
                'submitted_at' => now(),
            ]);

        // Entity own by different user.
        SalesOrder::factory()
            ->for($anotherUser = User::factory())
            ->for(
                WorldwideQuote::factory()
                    ->for(
                        Opportunity::factory()
                            ->for(SalesUnit::factory())
                    )
            )
            ->create([
                'submitted_at' => now(),
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/sales-orders/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
    }

    /**
     * Test an ability to view drafted sales orders listing.
     */
    public function testCanViewSubmittedSalesOrdersListing(): void
    {
        $this->authenticateApi();

        $endUser = Company::factory()->create();
        $accountMgr = User::factory()->create();

        /** @var SalesOrder[] $orders */
        $orders = factory(SalesOrder::class, 10)->create([
            'assets_count' => 3, 'submitted_at' => now(), 'failure_reason' => $this->faker->text(),
        ]);

        foreach ($orders as $order) {
            $order->worldwideQuote->opportunity->endUser()->associate($endUser);
            $order->worldwideQuote->opportunity->accountManager()->associate($accountMgr)->save();
        }

        $this->getJson('api/sales-orders/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'contract_type_id',
                        'worldwide_quote_id',
                        'order_number',
                        'status',
                        'failure_reason',
                        'rfq_number',
                        'customer_name',
                        'company_name',
                        'end_user_name',
                        'account_manager_name',
                        'account_manager_email',
                        'assets_count',
//                        'sequence_number',
                        'order_type',
                        'opportunity_name',
                        'created_at',
                        'activated_at',
                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                            'resubmit',
                            'refresh_status',
                            'cancel',
                            'export',
                        ],
                    ],
                ],
                'links' => [
                    'first', 'last', 'prev', 'next',
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total',
                ],
            ]);
    }

    /**
     * Test an ability to draft a new Sales Order from Worldwide Quote.
     */
    public function testCanDraftNewSalesOrderFromWorldwideQuote(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        factory(SalesOrderTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
            'company_id' => $quote->activeVersion->company_id,
        ]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/sales-order-data')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'worldwide_quote_number',
                'company' => [
                    'id', 'name', 'logo_url',
                ],
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo_url',
                    ],
                ],
                'countries' => [
                    '*' => [
                        'id', 'name', 'flag_url',
                    ],
                ],
                'sales_order_templates' => [
                    '*' => [
                        'id', 'name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('sales_order_templates'));

        $worldwideQuoteKey = $response->json('worldwide_quote_id');
        $contractTemplateKey = $response->json('sales_order_templates.0.id');

        $this->postJson('api/sales-orders', [
            'worldwide_quote_id' => $worldwideQuoteKey,
            'sales_order_template_id' => $contractTemplateKey,
            'vat_number' => Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => Str::random(191),
            'contract_number' => Str::random(100),
        ])
//            ->dump()
            ->assertInvalid(['contract_number'], responseKey: 'Error.original');

        $this->postJson('api/sales-orders', [
            'worldwide_quote_id' => $worldwideQuoteKey,
            'sales_order_template_id' => $contractTemplateKey,
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191),
            'contract_number' => $contractNumber = Str::random(50),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'sales_order_template_id',
                'vat_number',
                'customer_po',
                'contract_number',
            ])
            ->assertJson([
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo,
                'contract_number' => $contractNumber,
            ]);
    }

    /**
     * Test an ability to view state of an existing Sales Order.
     */
    public function testCanViewStateOfSalesOrder(): void
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();

        $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'sales_order_template_id',
                'order_date',
                'order_number',
                'status',
                'vat_number',
                'customer_po',
                'created_at',
                'updated_at',
                'submitted_at',
                'activated_at',
            ]);
    }

    /**
     * Test an ability to view mapped preview data of an existing Pack Sales Order.
     */
    public function testCanViewPreviewDataOfPackSalesOrder(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $multiYearDiscount = factory(MultiYearDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $quote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
        ]);

        $defaultSoftwareAddress = factory(Address::class)->create([
            'address_type' => 'Software', 'address_1' => '-1 Default Software Address',
        ]);
        $softwareAddress2 = factory(Address::class)->create(['address_type' => 'Software']);

        $quote->activeVersion->addresses()->sync([
            $softwareAddress2->getKey(),
            $defaultSoftwareAddress->getKey(),
        ]);

        factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $quote->getKey(),
            'is_selected' => true,
        ]);

        $salesOrder = factory(SalesOrder::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->getJson('api/sales-orders/'.$salesOrder->getKey().'/preview')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'template_data' => [
                    'first_page_schema',
                    'assets_page_schema',
                    'payment_schedule_page_schema',
                    'last_page_schema',
                    'template_assets',
                ],

                'quote_summary' => [
                    'company_name',
                    'quotation_number',
                    'customer_name',
                    'service_levels',
                    'invoicing_terms',
                    'support_start',
                    'support_end',
                    'valid_until',
                    'contact_name',
                    'contact_email',
                    'contact_phone',
                    'list_price',
                    'final_price',
                    'applicable_discounts',
                    'sub_total_value',
                    'total_value_including_tax',
                    'quote_price_value_coefficient',
                    'equipment_address',
                    'hardware_contact',
                    'hardware_phone',
                    'software_address',
                    'software_contact',
                    'software_phone',
                    'coverage_period',
                    'coverage_period_from',
                    'coverage_period_to',
                    'additional_details',
                    'pricing_document',
                    'service_agreement_id',
                    'system_handle',
                ],

                'pack_assets' => [
                    '*' => [
                        'product_no',
                        'description',
                        'serial_no',
                        'date_from',
                        'date_to',
                        'qty',
                        'price',
                        'price_float',
                        'pricing_document',
                        'system_handle',
                        'searchable',
                        'service_level_description',
                        'machine_address_string',
                    ],
                ],

                'pack_asset_fields' => [
                    '*' => [
                        'field_name',
                        'field_header',
                    ],
                ],
            ]);

        // TODO: asset the price field is missing.
    }

    /**
     * Test an ability to update an existing Sales Order.
     */
    public function testCanUpdateSalesOrder(): void
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();

        $salesOrderTemplate = factory(SalesOrderTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
        ]);

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey(), [

            'sales_order_template_id' => $salesOrderTemplate->getKey(),
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191),
            'contract_number' => Str::random(51),
        ])
//            ->dump()
            ->assertInvalid('contract_number', responseKey: 'Error.original');

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey(), [

            'sales_order_template_id' => $salesOrderTemplate->getKey(),
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191),
            'contract_number' => Str::random(50),
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sales_order_template_id',
                'vat_number',
                'customer_po',
            ])
            ->assertJson([
                'sales_order_template_id' => $salesOrderTemplate->getKey(),
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo,
            ]);

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey(), [

            'sales_order_template_id' => $salesOrderTemplate->getKey(),
            'vat_number' => null,
            'vat_type' => 'NO VAT',
            'customer_po' => $customerPo = Str::random(191),
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sales_order_template_id',
                'vat_number',
                'customer_po',
            ])
            ->assertJson([
                'sales_order_template_id' => $salesOrderTemplate->getKey(),
                'vat_number' => null,
                'customer_po' => $customerPo,
            ]);
    }

    /**
     * Test an ability to submit an existing Pack Sales Order.
     */
    public function testCanSubmitPackSalesOrder(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create([
            'contract_type_id' => CT_PACK,
        ]);

        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);
        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $quote->activeVersion->addresses()->sync([
            $invoiceAddress->getKey(), $machineAddress->getKey(),
        ]);

        $quote->activeVersion->update([
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        $quote->activeVersion->company()->associate(
            Company::query()->whereNotNull('vs_company_code')->firstOrFail()
        )->save();

        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => true,
            'vendor_id' => Vendor::query()->where('short_code', 'IBM')->value('id'),
            'sku' => 'J9846A',
            'serial_no' => 'CN51G8M1X6',
            'service_level_description' => 'HPE 1Y PW FC CTR 560 Wrls AP SVC',
            'machine_address_id' => $machineAddress->getKey(),
        ]);

        $salesOrder = factory(SalesOrder::class)->create([
            'worldwide_quote_id' => $quote->getKey(), 'submitted_at' => null, 'vat_number' => '1234',
        ]);

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response([
                'token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234',
            ]),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(fn() => $oauthFactory);

        /** @var HttpFactory $oauthFactory */
        $httpFactory = $this->app[HttpFactory::class];

        $httpFactory->fake([
            '*' => HttpFactory::response(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109']),
        ]);

        $this->app->when(SubmitSalesOrderService::class)->needs(HttpFactory::class)
            ->give(fn() => $httpFactory);


        $response = $this->postJson('api/sales-orders/'.$salesOrder->getKey().'/submit')
//                        ->dump()
        ;

        $this->assertContainsEquals($response->status(),
            [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_ACCEPTED]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to submit an existing Pack Sales Order.
     */
    public function testCanSubmitContractSalesOrder(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create([
            'contract_type_id' => CT_CONTRACT,
        ]);

        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);
        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $quote->activeVersion->addresses()->sync([
            $invoiceAddress->getKey(), $machineAddress->getKey(),
        ]);

        $quote->activeVersion->update([
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        $distributorFile = factory(QuoteFile::class)->create();

        $supplier = factory(OpportunitySupplier::class)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        /** @var WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'distributor_file_id' => $distributorFile,
            'opportunity_supplier_id' => $supplier->getKey(),
            'country_id' => Country::query()->value('id'),
            'use_groups' => true,
        ]);

        $distributorQuote->addresses()->sync([
            $invoiceAddress->getKey(),
            $machineAddress->getKey(),
        ]);

        $distributorQuote->vendors()->sync(Vendor::query()->where('short_code', 'HPE')->value('id'));

        $mappedRows = factory(MappedRow::class, 10)->create([
            'quote_file_id' => $distributorFile->getKey(),
        ]);

        /** @var DistributionRowsGroup $groupOfRows */
        $groupOfRows = factory(DistributionRowsGroup::class)->create([
            'worldwide_distribution_id' => $distributorQuote->getKey(),
            'is_selected' => true,
        ]);

        $groupOfRows->rows()->sync($mappedRows);

        $salesOrder = factory(SalesOrder::class)->create([
            'worldwide_quote_id' => $quote->getKey(), 'submitted_at' => null, 'vat_number' => '1234',
        ]);

        $this->app->when(SubmitSalesOrderService::class)->needs(ClientInterface::class)
            ->give(function () {
                $mock = new MockHandler([
                    new \GuzzleHttp\Psr7\Response(200, [],
                        json_encode(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234'])),
                    new \GuzzleHttp\Psr7\Response(200, [],
                        json_encode(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'])),
                ]);

                $handlerStack = HandlerStack::create($mock);

                return new Client(['handler' => $handlerStack]);
            });


        $response = $this->postJson('api/sales-orders/'.$salesOrder->getKey().'/submit')//            ->dump()
        ;

        $this->assertContainsEquals($response->status(),
            [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_ACCEPTED]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to cancel an existing Sales Order with specified reason.
     */
    public function testCanCancelSalesOrder(): void
    {
        $this->markTestIncomplete("Failure reason message is not replaced with generic text on server error.");

        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response([
                'token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234',
            ]),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(function () use ($oauthFactory) {
            return $oauthFactory;
        });

        /** @var HttpFactory $factory */
        $factory = $this->app[HttpFactory::class];

        $factory->fakeSequence()
            ->push(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'])
            ->push(
                json_decode('{"ErrorCode":"INVDT-08","ErrorUrl":"http:\/\/10.22.14.13\/bc-customer","Error":{"headers":{},"original":{"message":"Server Error"},"exception":null},"ErrorDetails":"Invalid Data Provided."}',
                    true),
                422,
            );

        $factory->fake([
            '*' => ['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'],
        ]);

        $this->app->when(CancelSalesOrderService::class)->needs(HttpFactory::class)->give(function () use ($factory) {
            return $factory;
        });

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/cancel', [
            'status_reason' => 'Just cancelled',
        ])
//            ->dump()
            ->assertStatus(Response::HTTP_ACCEPTED)
            ->assertJsonStructure([
                'result',
            ]);

        $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'status',
                'status_reason',
            ])
            ->assertJson([
                'status' => 3,
                'status_reason' => 'Just cancelled',
            ]);

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/cancel', [
            'status_reason' => 'Just cancelled',
        ])
//            ->dump()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'result',
                'failure_reason',
            ])
            ->assertJson([
                'result' => 'failure',
                'failure_reason' => "There was an issue while processing your sales order.\nPlease contact Software Team.\nThanks!",
            ]);
    }

    /**
     * Test an ability to mark an existing Sales Order as active.
     */
    public function testCanMarkSalesOrderAsActive(): void
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create(['activated_at' => null]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/activate')
            ->assertNoContent();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark an existing Sales Order as inactive.
     */
    public function testCanMarkSalesOrderAsInactive(): void
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create(['activated_at' => now()]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);


        $this->assertNotEmpty($response->json('activated_at'));

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/deactivate')
            ->assertNoContent();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to delete an existing Sales Order.
     */
    public function testCanDeleteSalesOrder(): void
    {
        $this->authenticateApi();

        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        factory(SalesOrderTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
            'company_id' => $quote->activeVersion->company_id,
        ]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/sales-order-data')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'worldwide_quote_number',
                'company' => [
                    'id', 'name', 'logo_url',
                ],
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo_url',
                    ],
                ],
                'countries' => [
                    '*' => [
                        'id', 'name', 'flag_url',
                    ],
                ],
                'sales_order_templates' => [
                    '*' => [
                        'id', 'name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('sales_order_templates'));

        $worldwideQuoteKey = $response->json('worldwide_quote_id');
        $orderTemplateKey = $response->json('sales_order_templates.0.id');

        $response = $this->postJson('api/sales-orders', [
            'worldwide_quote_id' => $worldwideQuoteKey,
            'sales_order_template_id' => $orderTemplateKey,
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'sales_order_template_id',
                'vat_number',
                'customer_po',
            ])
            ->assertJson([
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo,
            ]);

        $salesOrderModelKey = $response->json('id');

        $this->deleteJson('api/sales-orders/'.$salesOrderModelKey)
            ->assertNoContent();

        $this->getJson('api/sales-orders/'.$salesOrderModelKey)
            ->assertNotFound();
    }

    /**
     * Test an ability to export a submitted Sales Order.
     */
    public function testCanExportSalesOrder(): void
    {
        $this->authenticateApi();

        $quoteTemplate = factory(QuoteTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE, 'contract_type_id' => CT_CONTRACT,
        ]);
        $salesOrderTemplate = factory(SalesOrderTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE, 'contract_type_id' => CT_CONTRACT,
        ]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
            'submitted_at' => now(),
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $quoteTemplate->getKey(),
        ]);

        /** @var Opportunity $opportunity */
        $opportunity = $wwQuote->opportunity;

        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Models\Address::class)->create(['address_type' => 'Hardware'])
        );
        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Models\Address::class)->create(['address_type' => 'Software'])
        );

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(PromotionalDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(MultiYearDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create(
            [
                'worldwide_quote_id' => $wwQuote->getKey(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'buy_price' => mt_rand(500, 2000),
                'additional_details' => $this->faker->text(),
            ]
        );

        $templateFields = TemplateField::query()->where('is_system', true)
            ->whereNotIn('name', ['pricing_document', 'system_handle', 'searchable'])
            ->pluck('id', 'name');
        $importableColumns = ImportableColumn::query()->where('is_system', true)->pluck('id', 'name');

        $mapping = [];

        foreach ($templateFields as $name => $key) {
            $mapping[$key] = [
                'importable_column_id' => $importableColumns->get($name),
            ];
        }

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($mapping, $vendor) {
            $distribution->vendors()->sync($vendor);

            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $mappedRows = factory(MappedRow::class, 2)->create([
                'quote_file_id' => $distributorFile->getKey(), 'is_selected' => true,
            ]);

            $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $distribution->getKey()]);
            $rowsGroup->rows()->sync($mappedRows->modelKeys());

            $distribution->templateFields()->sync($mapping);

            $paymentScheduleFile = factory(QuoteFile::class)->create([
                'file_type' => 'Payment Schedule',
            ]);

            factory(ScheduleData::class)->create([
                'quote_file_id' => $paymentScheduleFile->getKey(),
            ]);

            $distribution->schedule_file_id = $paymentScheduleFile->getKey();
            $distribution->save();
        });

        $salesOrder = factory(SalesOrder::class)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'order_number' => sprintf("EPD-WW-DP-CSO%'.07d", $wwQuote->sequence_number),
            'sales_order_template_id' => $salesOrderTemplate->getKey(),
            'submitted_at' => now(),
        ]);

        $expectedFileName = sprintf("EPD-WW-DP-CSO%'.07d.pdf", $wwQuote->sequence_number);

        $this->getJson('api/sales-orders/'.$salesOrder->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");
    }

    /**
     * Test an ability to view list of reasons to cancel sales order.
     */
    public function testCanViewCancelSalesOrderReasonsList(): void
    {
        $this->authenticateApi();

        $this->getJson('api/sales-orders/cancel-reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'description',
                ],
            ]);
    }

    /**
     * Test an ability to refresh status of an existing sales order.
     */
    public function testCanRefreshStatusOfSalesOrder(): void
    {
        /** @var SalesOrder $salesOrder */
        $salesOrder = factory(SalesOrder::class)->create([
            'submitted_at' => now(),
            'status' => 2,
        ]);

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response([
                'token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234',
            ]),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(fn() => $oauthFactory);

        /** @var HttpFactory $factory */
        $factory = $this->app[HttpFactory::class];

        $factory->fake([
            '*' => $factory->response([
                'id' => $vsCustomerID = $this->faker->uuid,
                'bc_customer_id' => $this->faker->uuid,
                'bc_company' => 'EPD',
                'customer_number' => 'EPD-EQ-0000001',
                'customer_name' => $salesOrder->worldwideQuote->opportunity->primaryAccount->name,
                'status' => 1,
                'is_supplier' => 0,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'bc_orders' => [
                    [
                        'id' => $vsOrderID = $this->faker->uuid,
                        'customer_id' => $vsCustomerID,
                        'post_sales_id' => $salesOrder->getKey(),
                        'order_no' => $salesOrder->order_number,
                        'order_date' => now()->toDateString(),
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                        'status' => 1,
                        'bc_sales_line' => [
                            [
                                'id' => $this->faker->uuid,
                                'bc_order_id' => $vsOrderID,
                                'drop_shipment' => 0,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ],
                        ],
                        'bc_addresses' => [
                            [
                                'id' => $this->faker->uuid,
                                'customer_id' => $vsCustomerID,
                                'order_id' => $vsOrderID,
                                'address_type' => 'Invoice',
                                'is_default' => 1,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->app->when(CheckSalesOrderService::class)
            ->needs(HttpFactory::class)
            ->give(fn() => $factory);

        $this->authenticateApi();

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/refresh-status')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'status',
                'failure_reason',
            ])
            ->assertJson([
                'status' => 1,
            ]);

    }
}
