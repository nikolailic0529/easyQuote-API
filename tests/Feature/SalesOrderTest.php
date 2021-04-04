<?php

namespace Tests\Feature;

use App\Models\Address;
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
use App\Models\SalesOrder;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Services\SalesOrder\CancelSalesOrderService;
use App\Services\SalesOrder\SubmitSalesOrderService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
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
    public function testCanViewDraftedSalesOrdersListing()
    {
        $this->authenticateApi();

        factory(SalesOrder::class, 30)->create(['submitted_at' => null]);

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
//                        'sequence_number',
                        'order_type',
                        'created_at',
                        'activated_at'
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'
                ]
            ]);
    }

    /**
     * Test an ability to view drafted sales orders listing.
     *
     * @return void
     */
    public function testCanViewSubmittedSalesOrdersListing()
    {
        $this->authenticateApi();

        factory(SalesOrder::class, 30)->create(['submitted_at' => now(), 'failure_reason' => $this->faker->text()]);

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
//                        'sequence_number',
                        'order_type',
                        'created_at',
                        'activated_at'
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'
                ]
            ]);
    }

    /**
     * Test an ability to draft a new Sales Order from Worldwide Quote.
     *
     * @return void
     */
    public function testCanDraftNewSalesOrderFromWorldwideQuote()
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        factory(ContractTemplate::class)->create([
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
                    'id', 'name', 'logo_url'
                ],
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo_url'
                    ]
                ],
                'countries' => [
                    '*' => [
                        'id', 'name', 'flag_url'
                    ]
                ],
                'contract_templates' => [
                    '*' => [
                        'id', 'name'
                    ]
                ]
            ]);

        $this->assertNotEmpty($response->json('contract_templates'));

        $worldwideQuoteKey = $response->json('worldwide_quote_id');
        $contractTemplateKey = $response->json('contract_templates.0.id');

        $this->postJson('api/sales-orders', [
            'worldwide_quote_id' => $worldwideQuoteKey,
            'contract_template_id' => $contractTemplateKey,
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191)
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'contract_template_id',
                'vat_number',
                'customer_po'
            ])
            ->assertJson([
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo
            ]);
    }

    /**
     * Test an ability to view state of an existing Sales Order.
     *
     * @return void
     */
    public function testCanViewStateOfSalesOrder()
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();

        $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'contract_template_id',
                'order_date',
                'order_number',
                'status',
                'vat_number',
                'customer_po',
                'created_at',
                'updated_at',
                'submitted_at',
                'activated_at'
            ]);
    }

    /**
     * Test an ability to view mapped preview data of an existing Pack Sales Order.
     *
     * @return void
     */
    public function testCanViewPreviewDataOfPackSalesOrder()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK
        ]);

        $quote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc'
        ]);

        $defaultSoftwareAddress = factory(Address::class)->create(['address_type' => 'Software', 'address_1' => '-1 Default Software Address']);
        $softwareAddress2 = factory(Address::class)->create(['address_type' => 'Software']);

        $quote->opportunity->addresses()->sync([
            $softwareAddress2->getKey() => ['is_default' => false],
            $defaultSoftwareAddress->getKey() => ['is_default' => true]
        ]);

        factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $quote->getKey(),
            'is_selected' => true
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
                    'template_assets'
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
                        'machine_address_string'
                    ]
                ],

                'pack_asset_fields' => [
                    '*' => [
                        'field_name',
                        'field_header'
                    ]
                ]
            ]);

        // TODO: asset the price field is missing.
    }

    /**
     * Test an ability to update an existing Sales Order.
     *
     */
    public function testCanUpdateSalesOrder()
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();

        $contractTemplate = factory(ContractTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
        ]);

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey(), [

            'contract_template_id' => $contractTemplate->getKey(),
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191)
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'contract_template_id',
                'vat_number',
                'customer_po'
            ])
            ->assertJson([
                'contract_template_id' => $contractTemplate->getKey(),
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo
            ]);

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey(), [

            'contract_template_id' => $contractTemplate->getKey(),
            'vat_number' => null,
            'vat_type' => 'NO VAT',
            'customer_po' => $customerPo = Str::random(191)
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'contract_template_id',
                'vat_number',
                'customer_po'
            ])
            ->assertJson([
                'contract_template_id' => $contractTemplate->getKey(),
                'vat_number' => null,
                'customer_po' => $customerPo
            ]);
    }

    /**
     * Test an ability to submit an existing Pack Sales Order.
     *
     * @return void
     */
    public function testCanSubmitPackSalesOrder()
    {
        $this->authenticateApi();

        /** @var Opportunity $opportunity */
        $opportunity = factory(Opportunity::class)->create([
            'contract_type_id' => CT_PACK
        ]);

        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);
        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id')
        ]);

        $opportunity->addresses()->sync([
            $invoiceAddress->getKey(), $machineAddress->getKey()
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $quote->activeVersion->update([
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);


        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => true,
            'vendor_id' => Vendor::query()->where('short_code', 'IBM')->value('id'),
            'sku' => 'J9846A',
            'serial_no' => 'CN51G8M1X6',
            'service_level_description' => 'HPE 1Y PW FC CTR 560 Wrls AP SVC',
            'machine_address_id' => $machineAddress->getKey()
        ]);

        $salesOrder = factory(SalesOrder::class)->create(['worldwide_quote_id' => $quote->getKey(), 'submitted_at' => null, 'vat_number' => '1234']);

        $this->app->when(SubmitSalesOrderService::class)->needs(ClientInterface::class)
            ->give(function () {
               $mock = new MockHandler([
                   new \GuzzleHttp\Psr7\Response(200, [], json_encode(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234'])),
                   new \GuzzleHttp\Psr7\Response(200, [], json_encode(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'])),
               ]);

               $handlerStack = HandlerStack::create($mock);

               return new Client(['handler' => $handlerStack]);
            });


        $response = $this->postJson('api/sales-orders/'.$salesOrder->getKey().'/submit')
//            ->dump()
        ;

        $this->assertContainsEquals($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_ACCEPTED]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at'
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to submit an existing Pack Sales Order.
     *
     * @return void
     */
    public function testCanSubmitContractSalesOrder()
    {
        $this->authenticateApi();

        /** @var Opportunity $opportunity */
        $opportunity = factory(Opportunity::class)->create([
            'contract_type_id' => CT_CONTRACT
        ]);

        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);
        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id')
        ]);

        $opportunity->addresses()->sync([
            $invoiceAddress->getKey(), $machineAddress->getKey()
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $quote->activeVersion->update([
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        $distributorFile = factory(QuoteFile::class)->create();

        $supplier = factory(OpportunitySupplier::class)->create([
            'opportunity_id' => $opportunity->getKey()
        ]);

        /** @var WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'distributor_file_id' => $distributorFile,
            'opportunity_supplier_id' => $supplier->getKey(),
            'country_id' => Country::query()->value('id'),
            'use_groups' => true
        ]);

        $distributorQuote->addresses()->sync([
            $invoiceAddress->getKey(),
            $machineAddress->getKey()
        ]);

        $distributorQuote->vendors()->sync(Vendor::query()->where('short_code', 'HPE')->value('id'));

        $mappedRows = factory(MappedRow::class, 10)->create([
            'quote_file_id' => $distributorFile->getKey()
        ]);

        /** @var DistributionRowsGroup $groupOfRows */
        $groupOfRows = factory(DistributionRowsGroup::class)->create([
            'worldwide_distribution_id' => $distributorQuote->getKey(),
            'is_selected' => true
        ]);

        $groupOfRows->rows()->sync($mappedRows);

        $salesOrder = factory(SalesOrder::class)->create(['worldwide_quote_id' => $quote->getKey(), 'submitted_at' => null, 'vat_number' => '1234']);

        $this->app->when(SubmitSalesOrderService::class)->needs(ClientInterface::class)
            ->give(function () {
                $mock = new MockHandler([
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234'])),
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'])),
                ]);

                $handlerStack = HandlerStack::create($mock);

                return new Client(['handler' => $handlerStack]);
            });


        $response = $this->postJson('api/sales-orders/'.$salesOrder->getKey().'/submit')
//            ->dump()
        ;

        $this->assertContainsEquals($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_ACCEPTED]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at'
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to cancel an existing Sales Order with specified reason.
     *
     * @return void
     *
     */
    public function testCanCancelSalesOrder()
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create();


        $this->app->when(CancelSalesOrderService::class)->needs(ClientInterface::class)
            ->give(function () {
                $mock = new MockHandler([
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234'])),
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode(['id' => 'b7a34431-75c1-4b8d-af9d-4db0ca499109'])),
                ]);

                $handlerStack = HandlerStack::create($mock);

                return new Client(['handler' => $handlerStack]);
            });

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/cancel', [
            'status_reason' => 'Just cancelled'
        ])
            ->assertStatus(Response::HTTP_ACCEPTED)
            ->assertJsonStructure([
                'result'
            ]);

        $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'status',
                'status_reason'
            ])
            ->assertJson([
                'status' => 3,
                'status_reason' => 'Just cancelled'
            ]);
    }

    /**
     * Test an ability to mark an existing Sales Order as active.
     *
     * @return void
     */
    public function testCanMarkSalesOrderAsActive()
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create(['activated_at' => null]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/activate')
            ->assertNoContent();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at'
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark an existing Sales Order as inactive.
     *
     * @return void
     */
    public function testCanMarkSalesOrderAsInactive()
    {
        $this->authenticateApi();

        $salesOrder = factory(SalesOrder::class)->create(['activated_at' => now()]);

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at'
            ]);


        $this->assertNotEmpty($response->json('activated_at'));

        $this->patchJson('api/sales-orders/'.$salesOrder->getKey().'/deactivate')
            ->assertNoContent();

        $response = $this->getJson('api/sales-orders/'.$salesOrder->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to delete an existing Sales Order.
     *
     * @return void
     */
    public function testCanDeleteSalesOrder()
    {
        $this->authenticateApi();

        $this->authenticateApi();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        factory(ContractTemplate::class)->create([
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
                    'id', 'name', 'logo_url'
                ],
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo_url'
                    ]
                ],
                'countries' => [
                    '*' => [
                        'id', 'name', 'flag_url'
                    ]
                ],
                'contract_templates' => [
                    '*' => [
                        'id', 'name'
                    ]
                ]
            ]);

        $this->assertNotEmpty($response->json('contract_templates'));

        $worldwideQuoteKey = $response->json('worldwide_quote_id');
        $contractTemplateKey = $response->json('contract_templates.0.id');

        $response = $this->postJson('api/sales-orders', [
            'worldwide_quote_id' => $worldwideQuoteKey,
            'contract_template_id' => $contractTemplateKey,
            'vat_number' => $vatNumber = Str::random(191),
            'vat_type' => 'VAT Number',
            'customer_po' => $customerPo = Str::random(191)
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'contract_template_id',
                'vat_number',
                'customer_po'
            ])
            ->assertJson([
                'vat_number' => $vatNumber,
                'customer_po' => $customerPo
            ]);

        $salesOrderModelKey = $response->json('id');

        $this->deleteJson('api/sales-orders/'.$salesOrderModelKey)
            ->assertNoContent();

        $this->getJson('api/sales-orders/'.$salesOrderModelKey)
            ->assertNotFound();
    }

    /**
     * Test an ability to export a submitted Sales Order.
     *
     * @return void
     */
    public function testCanExportSalesOrder()
    {
        $this->authenticateApi();

        $quoteTemplate = factory(QuoteTemplate::class)->create(['business_division_id' => BD_WORLDWIDE, 'contract_type_id' => CT_CONTRACT]);
        $contractTemplate = factory(ContractTemplate::class)->create(['business_division_id' => BD_WORLDWIDE, 'contract_type_id' => CT_CONTRACT]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
            'submitted_at' => now()
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $quoteTemplate->getKey()
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
        factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

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
                'file_type' => QFT_WWPL
            ]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $mappedRows = factory(MappedRow::class, 2)->create(['quote_file_id' => $distributorFile->getKey(), 'is_selected' => true]);

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
            'contract_template_id' => $contractTemplate->getKey(),
            'submitted_at' => now()
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
     *
     * @return void
     */
    public function testCanViewCancelSalesOrderReasonsList()
    {
        $this->authenticateApi();

        $this->getJson('api/sales-orders/cancel-reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'description'
                ]
            ]);
    }
}
