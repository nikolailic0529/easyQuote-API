<?php

namespace Tests\Feature;

use App\Enum\PackQuoteStage;
use App\Models\Address;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Services\VendorServices\OauthClient as VSOauthClient;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class PackWorldwideQuoteTest
 * @group worldwide
 */
class WorldwidePackQuoteTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test an ability to initialize a new Pack Worldwide Quote.
     *
     * @return void
     */
    public function testCanInitPackWorldwideQuote()
    {
        $this->authenticateApi();

        \DB::table('opportunities')->update(['deleted_at' => now()]);

        $opportunities = factory(Opportunity::class, 2)->create();

        foreach ($opportunities as $opportunity) {
            /** @var Opportunity $opportunity */

            $address = factory(Address::class)->create();
            $contact = factory(Contact::class)->create();

            $opportunity->primaryAccount->addresses()->sync([$address->getKey() => ['is_default' => true]]);
            $opportunity->primaryAccount->contacts()->sync([$contact->getKey() => ['is_default' => true]]);
        }

        $opportunityWithSameCustomer = factory(Opportunity::class)->create([
            'primary_account_id' => $opportunities[0]->primary_account_id,
        ]);

        $opportunitySupplierWithSameCustomer = factory(OpportunitySupplier::class)->create([
            'opportunity_id' => $opportunityWithSameCustomer->getKey(),
        ]);

        /** @var WorldwideQuote $quoteWithSameCustomer */
        $quoteWithSameCustomer = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunityWithSameCustomer->getKey(),
        ]);

        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quoteWithSameCustomer->activeVersion->getKey(),
            'worldwide_quote_type' => $quoteWithSameCustomer->activeVersion->getMorphClass(),
        ]);

        $priceList = factory(QuoteFile::class)->create([
            'file_type' => 'Worldwide Distributor Price List',
        ]);

        factory(MappedRow::class)->create([
            'quote_file_id' => $priceList->getKey(),
        ]);

        /** @var WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $quoteWithSameCustomer->activeVersion->getKey(),
            'worldwide_quote_type' => $quoteWithSameCustomer->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $opportunitySupplierWithSameCustomer->getKey(),
        ]);

        $distributorQuote->vendors()->sync(Vendor::query()->value('id'));

        $distributorQuote->update([
            'distributor_file_id' => $priceList->getKey(),
        ]);

        // Pick a random opportunity from the opportunities endpoint.
        $response = $this->getJson('api/opportunities')->assertOk();

        $this->assertContains($opportunityId = $opportunities[0]->getKey(), $response->json('data.*.id'));

        // Perform initialize Worldwide Quote request.
        $response = $this->postJson('api/ww-quotes', [
            'opportunity_id' => $opportunityId,
            'contract_type' => 'pack',
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'opportunity_id',
                'company_id',
                'quote_currency_id',
                'output_currency_id',
                'exchange_rate_margin',
                'actual_exchange_rate',
                'target_exchange_rate',
                'completeness',
                'quote_expiry_date',
                'closing_date',
//                'checkbox_status',
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/ww-quotes/'.$response->json('id').'?include[]=opportunity.addresses&include[]=opportunity.contacts&include[]=assets')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'opportunity' => [
                    'id',
                    'addresses' => [
                        '*' => ['id'],
                    ],
                    'contacts' => [
                        '*' => ['id'],
                    ],
                ],
                'assets' => [
                    '*' => ['id'],
                ],
            ]);

        $this->assertNotEmpty($response->json('opportunity.addresses'));
        $this->assertNotEmpty($response->json('opportunity.contacts'));

        // Assert that assets was loaded from previously created assets of the primary account.
        $this->assertNotEmpty($response->json('assets'));
        $this->assertCount(2, $response->json('assets'));
    }

    /**
     * Test an ability to initialize a new worldwide quote asset.
     *
     * @return void
     *
     */
    public function testCanInitWorldwideQuoteAsset()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets')
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
            ]);

        $machineAddress = factory(Address::class)->create();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets', [
            'buy_currency_id' => Currency::query()->value('id'),
            'vendor_id' => Vendor::query()->value('id'),
            'machine_address_id' => $machineAddress->getKey(),
            'country' => 'GB',
            'serial_no' => $this->faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'),
            'sku' => $this->faker->regexify('/\d{6}-[A-Z]\d{2}/'),
            'service_sku' => $this->faker->regexify('/\d{6}-[A-Z]\d{2}/'),
            'product_name' => $this->faker->linuxProcessor,
            'expiry_date' => '2021-02-28T00:00:00.000Z',
            'service_level_description' => 'Post Warranty, ADP, Next Business Day Onsite, excl. ext. Mon., HW Supp, 1 year',
            'price' => $this->faker->randomFloat(2, 100, 1000),
            'buy_price' => $this->faker->randomFloat(2, 100, 1000),
            'buy_price_margin' => $this->faker->randomFloat(2, 0, 100),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'buy_currency_id',
                'vendor_id',
                'machine_address_id',
                'country',
                'serial_no',
                'sku',
                'service_sku',
                'product_name',
                'expiry_date',
                'service_level_description',
                'price',
                'buy_price',
                'buy_price_margin',
                'is_warranty_checked',
            ]);
    }

    /**
     * Test an ability to delete an existing worldwide quote asset.
     *
     * @return void
     */
    public function testCanDeleteWorldwideQuoteAsset()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets')
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
            ]);

        $this->deleteJson('api/ww-quotes/'.$quote->getKey().'/assets/'.$response->json('id'))
//            ->dump()
            ->assertNoContent();

        $this->deleteJson('api/ww-quotes/'.$quote->getKey().'/assets/'.$response->json('id'))
            ->assertNotFound();
    }

    /**
     * Test an ability to batch update the existing worldwide quote assets.
     *
     * @return void
     */
    public function testCanBatchUpdateWorldwideQuoteAssets()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $assetKeys = [];

        foreach (range(1, 10) as $i) {
            $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets')
//            ->dump()
                ->assertCreated()
                ->assertJsonStructure([
                    'id',
                ]);

            $assetKeys[] = $response->json('id');
        }

        $assetsData = array_map(function (string $assetKey) {

            return factory(WorldwideQuoteAsset::class)->raw([
                'id' => $assetKey,
            ]);

        }, $assetKeys);

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/assets',
            ['assets' => $assetsData, 'stage' => 'Assets Creation'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=assets&include[]=assets.machine_address.country')
//            ->dump()
            ->assertJsonStructure([
                'id',
                'assets' => [
                    '*' => ['id', 'vendor_short_code'],
                ],
            ]);

        $this->assertCount(10, $response->json('assets.*'));
    }

    /**
     * Test an ability to save pack assets with duplicate serial number.
     *
     * @return void
     */
    public function testCanNotBatchSaveWorldwideQuoteAssetsWithDuplicateSerialNumber()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $assetKeys = [];

        foreach (range(1, 10) as $i) {
            $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets')
//            ->dump()
                ->assertCreated()
                ->assertJsonStructure([
                    'id',
                ]);

            $assetKeys[] = $response->json('id');
        }

        $assetsData = array_map(function (string $assetKey) {

            return factory(WorldwideQuoteAsset::class)->raw([
                'id' => $assetKey,
            ]);

        }, $assetKeys);

        $assetsData[1]['serial_no'] = $assetsData[0]['serial_no'];

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/assets',
            ['assets' => $assetsData, 'stage' => 'Assets Creation'])
//            ->dump()
            ->assertUnprocessable()
            ->assertInvalid(['assets.0.serial_no', 'assets.1.serial_no'], responseKey: 'Error.original');
    }

    /**
     * Test an ability to perform batch warranty lookup on existing quote assets.
     *
     * @return void
     */
    public function testCanPerformBatchWarrantyLookup()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $assets = factory(WorldwideQuoteAsset::class, 2)->create([
            'worldwide_quote_id' => $quote->getKey(),
        ]);

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets/lookup', [
            'assets' => [
                [
                    'id' => $assets[0]->getKey(),
                    'index' => 3,
                    'vendor_short_code' => 'LEN',
                    'serial_no' => '06HNBAB',
                    'sku' => '5462K5G',
                    'country' => 'GB',
                    'buy_currency_code' => 'USD',
                ],
                [
                    'id' => $assets[1]->getKey(),
                    'index' => 4,
                    'vendor_short_code' => 'HPE',
                    'serial_no' => 'CN51G8M1X6',
                    'sku' => 'J9846A',
                    'country' => 'GB',
                    'buy_currency_code' => 'GBP',
                ],
            ],
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'index',
                    'serial_no',
                    'model',
                    'type',
                    'sku',
                    'service_sku',
                    'product_name',
                    'expiry_date',
                    'service_levels' => [
                        '*' => [
                            'description',
                            'price',
                        ],
                    ],
                ],
            ]);

        $this->assertContains(3, $response->json('*.index'));
        $this->assertContains(4, $response->json('*.index'));
    }

    /**
     * Test an ability to process the quote setup step of worldwide quote.
     *
     * @return void
     */
    public function testCanProcessWorldwideQuoteSetupStep()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $template = factory(QuoteTemplate::class)->create();

        $quoteExpiryDate = now()->addMonth();

        $stageData = [
            'company_id' => $companyKey = Company::query()->where('type', 'Internal')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'quote_template_id' => $templateKey = $template->getKey(),
            'quote_expiry_date' => $quoteExpiryDateString = $quoteExpiryDate->toDateString(),
            'buy_price' => $buyPrice = $this->faker->randomFloat(2, 1_000, 100_000),
            'are_end_user_addresses_available' => false,
            'are_end_user_contacts_available' => false,
            'payment_terms' => '30 Days',
            'stage' => 'Contacts',
        ];

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/contacts', $stageData)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=opportunity.addresses.country&include[]=opportunity.contacts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'are_end_user_addresses_available',
                'are_end_user_contacts_available',
                'opportunity' => [
                    'id',
                    'addresses' => [
                        '*' => [
                            'id',
                            'address_type',
                            'address_1',
                            'address_2',
                            'city',
                            'state',
                            'state_code',
                            'country_id',
                            'contact_name',
                            'contact_number',
                            'contact_email',
                            'country' => [
                                'id', 'iso_3166_2', 'name',
                            ],
                        ],
                    ],
                    'contacts' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'mobile',
                            'phone',
                        ],
                    ],
                ],
            ]);


        $this->assertEquals($companyKey, $response->json('company_id'));
        $this->assertEquals($templateKey, $response->json('quote_template_id'));
        $this->assertEquals($quoteExpiryDateString, $response->json('quote_expiry_date'));
        $this->assertEquals($buyPrice, $response->json('buy_price'));
        $this->assertSame($stageData['payment_terms'], $response->json('payment_terms'));
        $this->assertSame($stageData['are_end_user_addresses_available'], $response->json('are_end_user_addresses_available'));
        $this->assertSame($stageData['are_end_user_contacts_available'], $response->json('are_end_user_contacts_available'));
    }

    public function testCanUploadSampleNoVendorXlsxAsBatchAssetFile()
    {
        $file = UploadedFile::fake()->createWithContent(
            'sample-no-vendor.xlsx',
            file_get_contents(base_path('tests/Feature/Data/assets/sample-no-vendor.xlsx'))
        );

        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets/upload', [
            'file' => $file,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'file_id',
                'read_rows',
            ]);
    }

    /**
     * Test an ability to upload batch asset file.
     *
     * @return void
     */
    public function testCanUploadServicesCatalogUs20210216Xlsx()
    {
        $file = UploadedFile::fake()->createWithContent(
            '_Services_Catalog_US_2021-02-16.xlsx',
            file_get_contents(base_path('tests/Feature/Data/assets/_Services_Catalog_US_2021-02-16.xlsx'))
        );

        $quote = factory(WorldwideQuote::class)->create();

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets/upload', [
            'file' => $file,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'file_id',
                'read_rows' => [
                    '*' => [
                        'header', 'header_key', 'value',
                    ],
                ],
            ]);
    }

    /**
     * Test an ability to import batch asset file.
     *
     * @return void
     */
    public function testCanImportBatchAssetFile()
    {
        $file = UploadedFile::fake()->createWithContent(
            'sample-addresses.xlsx',
            file_get_contents(base_path('tests/Feature/Data/assets/sample-addresses.xlsx'))
        );

        $quote = factory(WorldwideQuote::class)->create();

        $this->authenticateApi();

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234']),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(fn() => $oauthFactory);

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets/upload', [
            'file' => $file,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'file_id',
                'read_rows' => [
                    '*' => [
                        'header', 'header_key', 'value',
                    ],
                ],
            ]);

        $fileUuid = $response->json('file_id');

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets/import', [
            'file_id' => $fileUuid,
            'headers' => [
                'serial_no' => 'serial_no',
                'sku' => 'product_no',
                'price' => 'unit_price',
                'expiry_date' => 'expiry_date',
                'country' => 'country',
                'street_address' => 'street_address',
                'vendor' => 'vendor',
                'service_level' => 'service_level',
            ],
            'file_contains_headers' => true,
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=opportunity.addresses&include[]=assets.machine_address.country')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'opportunity' => [
                    'id',
                    'addresses',
                ],
                'assets' => [
                    '*' => [
                        'id',
                        'serial_no',
                        'sku',
                        'product_name',
                        'expiry_date',
                        'price',
                        'is_warranty_checked',
                    ],
                ],
            ]);

        // Creates machine addresses for each asset with the filled street address column.
        $this->assertCount(5, array_filter($response->json('assets.*.machine_address')));

        // Attaches machine addresses created during the import.
        $this->assertCount(4, $response->json('opportunity.addresses'));
    }

    /**
     * Test an ability to process pack quote margin step.
     *
     * @return void
     */
    public function testCanProcessPackQuoteMarginStep()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/margin', [
            'tax_value' => 5.00,
            'margin_value' => 10.00,
            'margin_method' => 'No Margin',
            'quote_type' => 'New',
            'stage' => 'Margin',
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_type',
                'tax_value',
                'margin_value',
                'margin_method',
            ]);

        $this->assertEquals('New', $response->json('quote_type'));
        $this->assertEquals('No Margin', $response->json('margin_method'));
        $this->assertEquals(10.00, $response->json('margin_value'));
        $this->assertEquals(5.00, $response->json('tax_value'));

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/margin', [
            'tax_value' => null,
            'margin_value' => null,
            'margin_method' => null,
            'quote_type' => null,
            'stage' => 'Margin',
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_type',
                'margin_value',
                'margin_method',
                'tax_value',
            ]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_type',
                'margin_method',
                'margin_value',
                'tax_value',
                'stage',
            ]);

        $this->assertEquals('New', $response->json('quote_type'));
        $this->assertEquals('No Margin', $response->json('margin_method'));
        $this->assertNull($response->json('margin_value'));
        $this->assertNull($response->json('tax_value'));
        $this->assertEquals('Margin', $response->json('stage'));
    }

    /**
     * Test an ability to process pack quote assets review step.
     *
     * @return void
     */
    public function testCanProcessPackQuoteAssetsReviewStep()
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => false,
        ]);

        $this->authenticateApi();

        $selectedAssets = $assets->take(5);

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets-review', [
            'selected_rows' => $selectedAssets->modelKeys(),
            'selected_groups' => [],
            'use_groups' => false,
            'reject' => false,
            'sort_rows_column' => 'price',
            'sort_rows_direction' => 'desc',
            'stage' => 'Assets Review',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=assets')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'assets' => [
                    '*' => [
                        'id',
                    ],
                ],
                'sort_rows_column',
                'sort_rows_direction',
            ]);

        $actualSelectedAssets = array_filter($response->json('assets'), fn(array $asset) => $asset['is_selected']);
        $actualSelectedAssetKeys = Arr::pluck($actualSelectedAssets, 'replicated_asset_id');

        $expectedSelectedAssetKeys = $selectedAssets->modelKeys();

        sort($actualSelectedAssetKeys);
        sort($expectedSelectedAssetKeys);

        $this->assertEquals($actualSelectedAssetKeys, $expectedSelectedAssetKeys);

        $this->assertEquals('price', $response->json('sort_rows_column'));
        $this->assertEquals('desc', $response->json('sort_rows_direction'));
    }

    /**
     * Test an ability to view pack quote applicable discounts.
     *
     * @return void
     */
    public function testCanViewPackQuoteApplicableDiscounts()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $hpeVendor = Vendor::query()->where('short_code', 'HPE')->sole();
        $lenVendor = Vendor::query()->where('short_code', 'LEN')->sole();

        $lenAssets = factory(WorldwideQuoteAsset::class, 2)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => true,
            'vendor_id' => $lenVendor->getKey(),
        ]);

        $hpeAssets = factory(WorldwideQuoteAsset::class, 2)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => true,
            'vendor_id' => $hpeVendor->getKey(),
        ]);

        /** @var WorldwideQuoteAssetsGroup $groupOfAssets */
        $groupOfAssets = factory(WorldwideQuoteAssetsGroup::class)->create([
            'worldwide_quote_version_id' => $quote->activeVersion->getKey(),
            'is_selected' => true,
        ]);

        $groupOfAssets->assets()->sync($lenAssets->merge($hpeAssets));

        factory(MultiYearDiscount::class)->create([
            'vendor_id' => $lenVendor->getKey(),
        ]);
        factory(MultiYearDiscount::class)->create([
            'vendor_id' => $hpeVendor->getKey(),
        ]);
        factory(PrePayDiscount::class)->create([
            'vendor_id' => $lenVendor->getKey(),
        ]);
        factory(PrePayDiscount::class)->create([
            'vendor_id' => $hpeVendor->getKey(),
        ]);
        factory(PromotionalDiscount::class)->create([
            'vendor_id' => $lenVendor->getKey(),
        ]);
        factory(PromotionalDiscount::class)->create([
            'vendor_id' => $hpeVendor->getKey(),
        ]);
        factory(SND::class)->create([
            'vendor_id' => $lenVendor->getKey(),
        ]);
        factory(SND::class)->create([
            'vendor_id' => $hpeVendor->getKey(),
        ]);

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/applicable-discounts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'multi_year' => [
                    '*' => [
                        'id', 'name', 'durations' => [
                            'duration' => [
                                'value', 'duration',
                            ],
                        ],
                    ],
                ],
                'pre_pay' => [
                    '*' => [
                        'id', 'name', 'durations' => [
                            'duration' => [
                                'value', 'duration',
                            ],
                        ],
                    ],
                ],
                'promotional' => [
                    '*' => [
                        'id', 'name', 'value', 'minimum_limit',
                    ],
                ],
                'snd' => [
                    '*' => [
                        'id', 'name', 'value',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('multi_year'));
        $this->assertNotEmpty($response->json('pre_pay'));
        $this->assertNotEmpty($response->json('promotional'));
        $this->assertNotEmpty($response->json('snd'));

        $quote->activeVersion->update(['use_groups' => true]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/applicable-discounts')
//            ->dump()
            ->assertOk();

        $this->assertNotEmpty($response->json('multi_year'));
        $this->assertNotEmpty($response->json('pre_pay'));
        $this->assertNotEmpty($response->json('promotional'));
        $this->assertNotEmpty($response->json('snd'));
    }

    /**
     * Test an ability to view structured state of Pack Quote.
     *
     * @return void
     */
    public function testCanViewStateOfWorldwidePackQuote()
    {
        $opportunity = factory(Opportunity::class)->create(['contract_type_id' => CT_PACK]);

        $snDiscount = factory(SND::class)->create(['value' => 10]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $quote->activeVersion->update([
            'buy_price' => 2_000,
            'completeness' => PackQuoteStage::DISCOUNT,
            'margin_value' => 5,
            'custom_discount' => 2,
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id')
//            'sn_discount_id' => $snDiscount->getKey()
        ]);

        $assets = factory(WorldwideQuoteAsset::class, 20)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'is_selected' => true,
            'price' => 150,
            'buy_price' => 100,
        ]);

        /** @var Asset $sameAsset */
        $sameAsset = factory(Asset::class)->create([
            'serial_number' => $assets[0]->serial_no,
            'product_number' => $assets[0]->sku,
        ]);

        $sameAsset->companies()->sync(factory(Company::class)->create());

        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create([
            'worldwide_quote_version_id' => $quote->activeVersion->getKey(),
        ]);

        $assetsGroup->assets()->sync($assets->modelKeys());

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query([
                'include' => [
                    'summary', 'assets.machine_address', 'assets_groups.assets.machine_address',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'summary' => [
                    'total_price',
                    'final_total_price',
                    'applicable_discounts_value',
                    'buy_price',
                    'margin_percentage',
                ],
                'assets' => [
                    '*' => ['id', 'is_customer_exclusive_asset', 'is_warranty_checked', 'contract_duration', 'contract_duration_months'],
                ],
                'assets_groups' => [
                    '*' => [
                        'id',
                        'group_name',
                        'search_text',
                        'assets_sum',
                        'assets_count',
                        'assets' => [
                            '*' => [
                                'id',
                                'buy_currency_id',
                                'machine_address_string',
                                'buy_currency_code',
                                'is_customer_exclusive_asset',
                                'is_warranty_checked',
                            ],
                        ],
                    ],
                ],
                'completeness',
                'stage',
            ])
            ->assertJson([
                'summary' => [
                    'total_price' => '3000.00',
                    'final_total_price' => '3141.36',
                    'applicable_discounts_value' => '101.88',
                    'buy_price' => '2000.00',
                    'margin_percentage' => '33.33',
                    'final_margin' => '36.33',
                ],
            ]);

        $duplicatedAssetCount = 0;

        foreach ($response->json('assets') as $asset) {
            if ($asset['serial_no'] === $sameAsset->serial_number && $asset['sku'] === $sameAsset->product_number) {
                $this->assertFalse($asset['is_customer_exclusive_asset']);
                $this->assertArrayHasKey('owned_by_customer', $asset);
                $this->assertArrayHasKey('permissions', $asset['owned_by_customer']);
                $this->assertArrayHasKey('view', $asset['owned_by_customer']['permissions']);
                $this->assertArrayHasKey('update', $asset['owned_by_customer']['permissions']);
                $this->assertArrayHasKey('delete', $asset['owned_by_customer']['permissions']);
                $duplicatedAssetCount++;
            } else {
                $this->assertTrue($asset['is_customer_exclusive_asset']);
            }
        }

        $this->assertSame(1, $duplicatedAssetCount);
    }

    /**
     * Test an ability to apply predefined discounts on pack quote.
     *
     * @return void
     */
    public function testCanApplyPredefinedDiscountsOnPackQuote()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $muDiscount = factory(MultiYearDiscount::class)->create();
        $ppDiscount = factory(PrePayDiscount::class)->create();
        $prDiscount = factory(PromotionalDiscount::class)->create();
        $snDiscount = factory(SND::class)->create();

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=applicable_discounts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'applicable_discounts' => [
                    'multi_year_discounts' => [
                        '*' => [
                            'id', 'name', 'durations' => [
                                'duration' => [
                                    'value', 'duration',
                                ],
                            ],
                        ],
                    ],
                    'pre_pay_discounts' => [
                        '*' => [
                            'id', 'name', 'durations' => [
                                'duration' => [
                                    'value', 'duration',
                                ],
                            ],
                        ],
                    ],
                    'promotional_discounts' => [
                        '*' => [
                            'id', 'name', 'value', 'minimum_limit',
                        ],
                    ],
                    'sn_discounts' => [
                        '*' => [
                            'id', 'name', 'value',
                        ],
                    ],
                ],
            ]);

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/discounts', [
            'predefined_discounts' => [
                'multi_year_discount' => $myDiscountKey = $response->json('applicable_discounts.multi_year_discounts.0.id'),
                'pre_pay_discount' => $ppDiscountKey = $response->json('applicable_discounts.pre_pay_discounts.0.id'),
                'promotional_discount' => $prDiscountKey = $response->json('applicable_discounts.promotional_discounts.0.id'),
                'sn_discount' => $snDiscountKey = $response->json('applicable_discounts.sn_discounts.0.id'),
            ],
            'stage' => 'Discount',
        ])
//            ->dump()
            ->assertOk();

//
        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=predefined_discounts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'predefined_discounts' => [
                    'multi_year_discount' => [
                        'id', 'name', 'durations' => [
                            'duration' => [
                                'value', 'duration',
                            ],
                        ],
                    ],
                    'pre_pay_discount' => [
                        'id', 'name', 'durations' => [
                            'duration' => [
                                'value', 'duration',
                            ],
                        ],
                    ],
                    'promotional_discount' => [
                        'id', 'name', 'value', 'minimum_limit',
                    ],
                    'sn_discount' => [
                        'id', 'name', 'value',
                    ],
                ],
                'stage',
            ]);

        $this->assertEquals($myDiscountKey, $response->json('predefined_discounts.multi_year_discount.id'));
        $this->assertEquals($ppDiscountKey, $response->json('predefined_discounts.pre_pay_discount.id'));
        $this->assertEquals($prDiscountKey, $response->json('predefined_discounts.promotional_discount.id'));
        $this->assertEquals($snDiscountKey, $response->json('predefined_discounts.sn_discount.id'));
        $this->assertEquals('Discount', $response->json('stage'));
    }

    /**
     * Test an ability to apply custom discount on pack quote.
     *
     * @return void
     */
    public function testCanApplyCustomDiscountOnPackQuote()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/discounts', [
            'custom_discount' => 10.00,
            'stage' => 'Discount',
        ])
//            ->dump()
            ->assertOk();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJson([
                'custom_discount' => '10.00',
                'stage' => 'Discount',
            ]);
    }

    /**
     * Test an ability to process pack quote details step.
     *
     * @return void
     */
    public function testCanProcessPackQuoteDetailsStep()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $faker = $this->app[Generator::class];

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/details', [
            'pricing_document' => $pricingDocument = $faker->text(1000),
            'service_agreement_id' => $serviceAgreementID = $faker->text(1000),
            'system_handle' => $systemHandle = $faker->text(1000),
            'additional_details' => $additionalDetails = $faker->text(10000),
            'stage' => 'Additional Detail',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'pricing_document',
                'service_agreement_id',
                'system_handle',
                'additional_details',
                'stage',
            ]);

        $this->assertEquals($pricingDocument, $response->json('pricing_document'));
        $this->assertEquals($serviceAgreementID, $response->json('service_agreement_id'));
        $this->assertEquals($systemHandle, $response->json('system_handle'));
        $this->assertEquals($additionalDetails, $response->json('additional_details'));
        $this->assertEquals('Additional Detail', $response->json('stage'));
    }

    /**
     * Test an ability to view the preview data of an existing pack quote.
     *
     * @return void
     */
    public function testCanViewPackQuotePreviewData()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $quote->opportunity->update([
            'contract_duration_months' => 53,
//            'is_contract_duration_checked' => true,
        ]);

        $quote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
//            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
            'margin_value' => 10.00,
            'custom_discount' => 5,
            'tax_value' => 10,
            'buy_price' => 5000,
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'use_groups' => true,
        ]);

        $defaultSoftwareAddress = factory(Address::class)->create(['address_type' => 'Software', 'address_1' => '-1 Default Software Address']);
        $softwareAddress2 = factory(Address::class)->create(['address_type' => 'Software']);

        $quote->activeVersion->addresses()->sync([
            $softwareAddress2->getKey(),
            $defaultSoftwareAddress->getKey(),
        ]);

        $assets = factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'is_selected' => true,
            'buy_price' => 5000 / 5,
            'price' => 1000,
        ]);

        /** @var WorldwideQuoteAsset $assetWithGeneratedSerialNumber */
        $assetWithGeneratedSerialNumber = $assets[0];
        $assetWithGeneratedSerialNumber->is_serial_number_generated = true;
        $assetWithGeneratedSerialNumber->save();

        /** @var WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create([
            'worldwide_quote_version_id' => $quote->active_version_id,
            'is_selected' => true,
        ]);

        $assetsGroup->assets()->sync($assets);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/preview')
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
                    'contract_duration',
                    'is_contract_duration_checked',
                    'contact_name',
                    'contact_country',
                    'contact_email',
                    'contact_phone',
                    'end_user_name',
                    'end_user_contact_country',
                    'end_user_contact_name',
                    'end_user_contact_email',
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

                    'account_manager_name',

                    'quote_data_aggregation_fields' => [
                        '*' => ['field_name', 'field_header'],
                    ],

                    'quote_data_aggregation' => [
                        '*' => [
                            'vendor_name',
                            'country_name',
                            'duration',
                            'qty',
                            'total_price',
                        ],
                    ],
                ],


                'pack_assets' => [
                    '*' => [
                        'group_name',
                        'group_total_price',
                        'group_total_price_float',
                        'assets' => [
                            '*' => [
                                'product_no',
                                'description',
                                'serial_no',
                                'date_from',
                                'date_to',
                                'contract_duration',
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
                    ],
                ],

                'pack_asset_fields' => [
                    '*' => [
                        'field_name',
                        'field_header',
                    ],
                ],

                'asset_notes',
            ]);

        $this->assertNotEmpty($response->json('quote_summary.quote_data_aggregation'));
        $this->assertNotEmpty($response->json('quote_summary.quote_data_aggregation_fields'));

        $this->assertSame('£ 5,555.56', $response->json('quote_summary.list_price'));
        $this->assertSame('£ 5,263.16', $response->json('quote_summary.final_price'));
        $this->assertSame('£ 292.40', $response->json('quote_summary.applicable_discounts'));
        $this->assertSame('£ 5,263.16', $response->json('quote_summary.sub_total_value'));

        // Assert the asset with generated serial number is marked with asterisk.
        $this->assertContains($assetWithGeneratedSerialNumber->serial_no." **", $response->json('pack_assets.*.assets.*.serial_no'));
        $this->assertNotEmpty($response->json('asset_notes'));
        $this->assertStringStartsWith("*", $response->json('asset_notes'));
    }

    /**
     * Test an ability to view prepared sales order data of worldwide pack quote.
     *
     * @return void
     */
    public function testCanViewWorldwidePackQuoteSalesOrderData()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
        ]);

        factory(ContractTemplate::class)->create([
            'company_id' => $wwQuote->activeVersion->company_id,
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_PACK,
        ]);

        $defaultSoftwareAddress = factory(Address::class)->create(['address_type' => 'Software', 'address_1' => '-1 Default Software Address']);
        $softwareAddress2 = factory(Address::class)->create(['address_type' => 'Software']);

        $wwQuote->activeVersion->addresses()->sync([
            $softwareAddress2->getKey(),
            $defaultSoftwareAddress->getKey(),
        ]);

        factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'is_selected' => true,
        ]);

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/sales-order-data')
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
    }

    /**
     * Test an ability to export worldwide pack quote with inline assets.
     *
     * @return void
     */
    public function testCanExportWorldwidePackQuoteWithInlineAssets()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create([
            'form_data' => json_decode(file_get_contents(__DIR__.'/Data/quote-template/ww-pack-schema.json'), true),
        ]);

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'submitted_at' => now(),
        ]);

        $wwQuote->opportunity->primaryAccountContact()->associate(factory(Contact::class)->create([
            'email' => 'Francesco.Ziviani@surftech.com',
        ]));

        $wwQuote->opportunity->save();

        $wwQuote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'pricing_document' => Str::random(20),
            'service_agreement_id' => Str::random(20),
            'system_handle' => Str::random(20),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
        ]);

        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
        ]);

        $softwareAddress = factory(Address::class)->create([
            'address_type' => 'Software',
        ]);

        $hardwareContact = factory(Contact::class)->create([
            'contact_type' => 'Hardware',
        ]);

        $softwareContact = factory(Contact::class)->create([
            'contact_type' => 'Software',
        ]);

        $wwQuote->activeVersion->addresses()->sync([$machineAddress->getKey(), $softwareAddress->getKey()]);
        $wwQuote->activeVersion->contacts()->sync([$hardwareContact->getKey(), $softwareContact->getKey()]);

        factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'is_selected' => true,
        ]);

        $expectedFileName = $wwQuote->quote_number.'.pdf';

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");

//        $fakeStorage = Storage::persistentFake();
//
//        $fakeStorage->put($expectedFileName, $response->getContent());
//
//        \exec("xdg-open {$fakeStorage->path($expectedFileName)}");
    }

    /**
     * Test an ability to export worldwide pack quote,
     * when contract duration is checked.
     *
     * @return void
     */
    public function testCanExportWorldwidePackQuoteWhenContractDurationIsChecked()
    {
        $this->authenticateApi();

        $templateSchema = <<<TEMPLATE
{"last_page": [{"id": "ae296560-047e-4cd1-8a30-d4481159eb80", "name": "Single Column", "child": [{"id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "class": "col-lg-12", "controls": [{"id": "04bb6182-2768-486b-9961-1b5bdebaefbf", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Why choose Europlus Direct to deliver your Vendor Support Services?", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "attached_element_id": "ae296560-047e-4cd1-8a30-d4481159eb80"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "7487ada6-1182-4737-9a62-34c893e7fbd1", "name": "Single Column", "child": [{"id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "class": "col-lg-12", "controls": [{"id": "cd2c69d9-4956-494a-9a82-a20d4a7e3ed2", "src": null, "name": "richtext-1", "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><strong>Account Management</strong> – Your account manager will help you to manage your services contract, and will arrange quarterly support reviews for you to ensure that the service levels within the services contract remain appropriate for the applications running on the hardware. If your IT environment changes, with the addition or decommissioning of hardware, we can update your services contract at any time.</p><p><strong>Renewal Service</strong> – Your account manager will remind you when your services contract is due to expire, normally 45 to 90 days in advance. This gives us enough time to review your current IT support, take into account any changes that have taken place in your IT environment, and create an up-to-date tailored quotation.</p><p><strong>Assistance with Vendor tools</strong> – We are experienced in using the proprietary tools and resources available to make managing your IT support easier. We can help you to link your support with the Support Centre portal and introduce you to contacts that can assist with installing IRS.</p><p><strong>Support for the whole lifecycle</strong> – Europlus Direct can provide support for your IT environment from initial product purchase through to decommissioning and technology refresh.</p><p>Consolidate your IT Support – Your account manager will help you to consolidate your various IT support agreements and certificates under one Vendor services contract. This can include Vendor hardware and some multi-vendor hardware.</p><p><strong>Flexibility</strong> – Once a services contract is in place with Vendor, it is possible for you to add new hardware to the contract (with 30 days’ notice) or remove hardware from the contract (with 90 days’ notice). Any difference in cost will be invoiced or credited accordingly. Services Contracts can also be cancelled entirely, subject to minimum periods of cover and notice periods.</p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "attached_element_id": "7487ada6-1182-4737-9a62-34c893e7fbd1"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "328a2ad6-9090-478e-b719-b23489239dd1", "css": null, "src": null, "name": "h-1", "show": false, "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "9d794895-65cf-4656-be58-1d9a2715bb04", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "cbb82c75-c7a1-4cf9-be44-a1d60c696665", "css": null, "src": null, "name": "richtext-1", "show": false, "type": "richtext", "class": null, "label": "Rich Text", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "9d794895-65cf-4656-be58-1d9a2715bb04"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "data_pages": [{"id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47", "name": "Single Column", "child": [{"id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "class": "col-lg-12", "controls": [{"id": "820d7aea-804a-423b-8d63-3696f70ba365", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Quotation Detail", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "attached_element_id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Equipment Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "equipment_address", "name": "equipment_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Equipment Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Software Update Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_address", "name": "software_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Hardware Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_contact", "name": "hardware_contact", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Software Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_contact", "name": "software_contact", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_phone", "name": "hardware_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_phone", "name": "software_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "736ac1e0-52c8-4f80-bc81-7bae72e10102", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Coverage Period", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "coverage_period", "css": null, "name": "coverage_period", "show": false, "type": "tag", "class": "s4-form-control", "label": "Coverage Period", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [], "position": 4}], "class": "four-column field-dragger", "order": 5, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "4", "visibility": true}], "first_page": [{"id": "769a5d52-25c8-48f0-94f0-8d40a21aa244", "name": "Two Column", "child": [{"id": "4d3cad85-f5b0-46f0-84e2-76ab74ba9939", "class": "col-lg-3", "controls": [{"id": "company_logo_x3", "css": null, "src": "http://68.183.35.153/easyQuote-API/public/storage/images/companies/b86170d9-ceca-4f58-8a04-99ed2b0acb87@x3.png", "name": "company_logo_x3", "show": false, "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4d3cad85-f5b0-46f0-84e2-76ab74ba9939", "attached_element_id": "769a5d52-25c8-48f0-94f0-8d40a21aa244"}], "position": 1}, {"id": "e9c15bc2-a914-4fc4-88f7-6838e3c1fda4", "class": "col-lg-9 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "text-right", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "e9c15bc2-a914-4fc4-88f7-6838e3c1fda4", "attached_element_id": "769a5d52-25c8-48f0-94f0-8d40a21aa244"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "15a2c17a-6ea9-4668-8664-7c43566d7a1d", "name": "Single Column", "child": [{"id": "568fbf4d-ad85-49c4-a209-e2f7e3de4865", "class": "col-lg-12 border-right", "controls": [], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "company_name", "css": null, "name": "company_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "205bb944-4333-4add-baa7-5ae17bf238a9", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Company", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "customer_name", "css": null, "name": "customer_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "44ee34b7-4ab7-44da-9134-9994259f85d0", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Contact Name", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "contact_name", "css": null, "name": "contact_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79", "name": "Two Column", "child": [{"id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "class": "col-lg-3", "controls": [{"id": "04ae8306-7a58-41c5-8d95-8581bbc6bebb", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "blue", "label": "Label", "value": "Email", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "attached_element_id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79"}], "position": 1}, {"id": "4fe14638-75d9-4353-85a0-ec1e96793175", "class": "col-lg-9 border-right", "controls": [{"id": "contact_email", "css": null, "name": "contact_email", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Email", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4fe14638-75d9-4353-85a0-ec1e96793175", "attached_element_id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98", "name": "Two Column", "child": [{"id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "class": "col-lg-3", "controls": [{"id": "04ae8306-7a58-41c5-8d95-8581bbc6bebb", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "blue", "label": "Label", "value": "Phone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "attached_element_id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98"}], "position": 1}, {"id": "4fe14638-75d9-4353-85a0-ec1e96793175", "class": "col-lg-9 border-right", "controls": [{"id": "contact_phone", "css": null, "name": "contact_phone", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4fe14638-75d9-4353-85a0-ec1e96793175", "attached_element_id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "8c74b0d3-f439-4383-a442-e4386fb957d9", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12", "controls": [{"id": "ae1e9dc9-9458-4172-a2b7-6086a29ce1b0", "css": null, "src": null, "name": "h-1", "show": true, "type": "hr", "class": "blue", "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "8c74b0d3-f439-4383-a442-e4386fb957d9"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "a8661be5-1b81-43ad-a614-2fefc2db80eb", "name": "Single Column", "child": [{"id": "88647d86-d061-43d5-9078-1e232b5ea47e", "class": "col-lg-12", "controls": [{"id": "0b2402a3-38de-47f8-a0f6-83b7c3cc3764", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Quotation Summary", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "88647d86-d061-43d5-9078-1e232b5ea47e", "attached_element_id": "a8661be5-1b81-43ad-a614-2fefc2db80eb"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "e1b21561-9910-4375-b324-bbee46c8bbf4", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "quotation_number", "name": "quotation_number", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "RFQ Number", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "344e5c52-2caf-4376-a4b3-915ca7a12965", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Valid Until:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "valid_until", "name": "valid_until", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "Quotation Closing Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support Start Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}, {"id": "support_start_assumed_char", "css": null, "name": "support_start_assumed_char", "show": false, "type": "tag", "class": "s4-form-control", "label": "Support Start Assumed (*)", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": true}, {"id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support End Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}, {"id": "support_end_assumed_char", "css": null, "name": "support_end_assumed_char", "show": false, "type": "tag", "class": "s4-form-control", "label": "Support End Assumed (*)", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": true}, {"id": "d7575e1d-3cd8-4536-8d04-57a0f0bc7356", "name": "Two Column", "child": [{"id": "e0223105-5f01-42df-a17d-011959752e63", "class": "col-lg-4", "controls": [{"id": "5225b1b8-9fa5-4eae-9967-d1da265680e6", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "bold", "label": "Label", "value": "Support Duration", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "e0223105-5f01-42df-a17d-011959752e63", "attached_element_id": "d7575e1d-3cd8-4536-8d04-57a0f0bc7356"}], "position": 1}, {"id": "56f025b0-f00c-489d-9beb-8ae7bc89723d", "class": "col-lg-8 border-right", "controls": [{"id": "contract_duration", "css": null, "name": "contract_duration", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contract Duration", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "56f025b0-f00c-489d-9beb-8ae7bc89723d", "attached_element_id": "d7575e1d-3cd8-4536-8d04-57a0f0bc7356"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "List Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "list_price", "name": "list_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total List Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a7623070-c87b-428f-a894-87ccfcb909c2", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Applicable Discounts:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "applicable_discounts", "name": "applicable_discounts", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total Discounts", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Final Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "final_price", "name": "final_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Final Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}, {"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "(All prices are excluding applicable taxes)", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Payment Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "payment_terms", "css": null, "name": "payment_terms", "show": false, "type": "tag", "class": "s4-form-control", "label": "Payment Terms", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "9c378e04-49eb-472d-bead-5a02d628303b", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Contact Us", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "9c378e04-49eb-472d-bead-5a02d628303b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "eb4410df-3cb1-4e68-866a-f294658768b7", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Tel:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "+44 (0)113 887 8650", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "5591650b-9c22-4f55-a286-916b64aa5588", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Email:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "sales@epd-worldwide.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "fef1dba7-386f-4959-bd7d-121de4a0cd54", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Visit:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "www.europlusdirect.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "fa3a8604-9e8f-45a1-bb3e-246b8a099a92", "name": "Single Column", "child": [{"id": "37b9807a-e66d-4a73-94e3-62a99e6b48fb", "class": "col-lg-12 border-right", "controls": [{"id": "quote_data_aggregation", "css": null, "name": "quote_data_aggregation", "show": false, "type": "tag", "class": "s4-form-control", "label": "Quote Data Aggregation", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "37b9807a-e66d-4a73-94e3-62a99e6b48fb", "attached_element_id": "fa3a8604-9e8f-45a1-bb3e-246b8a099a92"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "0ef5539e-571c-49a6-8c51-a51c483b2f88", "name": "Single Column", "child": [{"id": "e5ffc37f-9096-4ea3-8f6f-305d0ea71463", "class": "col-lg-12 border-right", "controls": [{"id": "footer_notes", "css": null, "name": "footer_notes", "show": false, "type": "tag", "class": "s4-form-control", "label": "Footer notes", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "e5ffc37f-9096-4ea3-8f6f-305d0ea71463", "attached_element_id": "0ef5539e-571c-49a6-8c51-a51c483b2f88"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "payment_page": [{"id": "344b0e65-02bf-4be0-aa5f-499541389ba3", "name": "Single Column", "child": [{"id": "76564aad-95f2-4ed3-ad83-f15ff542a45d", "class": "col-lg-12 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "s4-form-control", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "76564aad-95f2-4ed3-ad83-f15ff542a45d", "attached_element_id": "344b0e65-02bf-4be0-aa5f-499541389ba3"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "vendor_name", "css": null, "name": "vendor_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Vendor Full Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "eb1e8eb2-2616-455c-aa10-52578736687e"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "c83dff46-b6ef-4553-bc69-775d3694a487", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "54682e57-49d0-47c0-bb51-44233fa41438", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "13612e0d-7a90-420f-88cc-73ea17046865", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "087c4ed7-f16b-4250-b025-69ec72801d2f", "src": null, "name": "h-1", "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "13612e0d-7a90-420f-88cc-73ea17046865"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "baadd19a-a0b7-491c-930b-e1325653c3ce", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "Payment Schedule From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "To", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}]}
TEMPLATE;

        $template = factory(QuoteTemplate::class)->create([
            'form_data' => json_decode($templateSchema, true),
        ]);

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'submitted_at' => now(),
        ]);

        $wwQuote->opportunity->update([
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 14,
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'pricing_document' => Str::random(20),
            'service_agreement_id' => Str::random(20),
            'system_handle' => Str::random(20),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
        ]);

        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
        ]);

        $softwareAddress = factory(Address::class)->create([
            'address_type' => 'Software',
        ]);

        $hardwareContact = factory(Contact::class)->create([
            'contact_type' => 'Hardware',
        ]);

        $softwareContact = factory(Contact::class)->create([
            'contact_type' => 'Software',
        ]);

        $wwQuote->activeVersion->addresses()->sync([$machineAddress->getKey(), $softwareAddress->getKey()]);
        $wwQuote->activeVersion->contacts()->sync([$hardwareContact->getKey(), $softwareContact->getKey()]);

        factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'is_selected' => true,
        ]);

        $expectedFileName = $wwQuote->quote_number.'.pdf';

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");

        @mkdir("storage/framework/testing/ww-quotes/", recursive: true);

        file_put_contents("storage/framework/testing/ww-quotes/$expectedFileName", $response->content());
    }

    /**
     * Test an ability to export worldwide pack quote with grouped assets.
     *
     * @return void
     */
    public function testCanExportWorldwidePackQuoteWithGroupedAssets()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create([
            'form_data' => json_decode('{"last_page": [{"id": "ae296560-047e-4cd1-8a30-d4481159eb80", "name": "Single Column", "child": [{"id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "class": "col-lg-12", "controls": [{"id": "04bb6182-2768-486b-9961-1b5bdebaefbf", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Why choose Support Warehouse to deliver your HPE Services Contract?", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "attached_element_id": "ae296560-047e-4cd1-8a30-d4481159eb80"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "7487ada6-1182-4737-9a62-34c893e7fbd1", "name": "Single Column", "child": [{"id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "class": "col-lg-12", "controls": [{"id": "cd2c69d9-4956-494a-9a82-a20d4a7e3ed2", "src": null, "name": "richtext-1", "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><strong>Account Management</strong> – Your account manager will help you to manage your services contract, and will arrange quarterly support reviews for you to ensure that the service levels within the services contract remain appropriate for the applications running on the hardware. If your IT environment changes, with the addition or decommissioning of hardware, we can update your services contract at any time.</p><p><strong>Renewal Service</strong> – Your account manager will remind you when your services contract is due to expire, normally 45 to 90 days in advance. This gives us enough time to review your current IT support, take into account any changes that have taken place in your IT environment, and create an up-to-date tailored quotation.</p><p><strong>Flexible Payment Options</strong> – You can choose invoicing terms to suit your budget and business preferences, as we offer upfront, annual or quarterly payment options (subject to terms and conditions). Please note that the payment and invoicing terms for this quote are stated in the quotation summary (final price is subject to change if invoicing terms are changed).</p><p><strong>Assistance with HPE tools</strong> – We are experienced in using the proprietary tools and resources available to make managing your IT support easier. We can help you to link your support with the Support Centre portal and introduce you to contacts that can assist with installing IRS.</p><p><strong>Support for the whole lifecycle</strong> – Support Warehouse can provide support for your IT environment from initial product purchase through to decommissioning and technology refresh.</p><p>Consolidate your IT Support – Your account manager will help you to consolidate your various IT support agreements and certificates under one HPE services contract. This can include HPE hardware and some multi-vendor hardware.</p><p><strong>Flexibility</strong> – Once a services contract is in place with HPE, it is possible for you to add new hardware to the contract (with 30 days’ notice) or remove hardware from the contract (with 90 days’ notice). Any difference in cost will be invoiced or credited accordingly. Services Contracts can also be cancelled entirely, subject to minimum periods of cover and notice periods.</p><p><br></p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "attached_element_id": "7487ada6-1182-4737-9a62-34c893e7fbd1"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "328a2ad6-9090-478e-b719-b23489239dd1", "css": null, "src": null, "name": "h-1", "show": false, "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "9d794895-65cf-4656-be58-1d9a2715bb04", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "cbb82c75-c7a1-4cf9-be44-a1d60c696665", "css": null, "src": null, "name": "richtext-1", "show": false, "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><span style=\"color: rgb(187, 187, 187);\">Company Reg.: 4056599, Company VAT: 758 5011 25&nbsp;&nbsp;</span></p><p><span style=\"color: rgb(187, 187, 187);\">UK registered company. Registered office: Support Warehouse Ltd, International Development Centre, Valley Drive, Ilkley, West Yorkshire, LS29 8PB</span></p><p><span style=\"color: rgb(187, 187, 187);\">Tel: 0800 072 0950</span></p><p><span style=\"color: rgb(187, 187, 187);\">Email: gb@supportwarehouse.com</span></p><p><span style=\"color: rgb(187, 187, 187);\">Visit: www.supportwarehouse.com/hpe-contract-services</span></p><p><span style=\"color: rgb(187, 187, 187);\">Terms &amp; Conditions: http://www.supportwarehouse.com/support-warehouse-limited-terms-conditions-business/</span></p><p><br></p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "9d794895-65cf-4656-be58-1d9a2715bb04"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "data_pages": [{"id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47", "name": "Single Column", "child": [{"id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "class": "col-lg-12", "controls": [{"id": "820d7aea-804a-423b-8d63-3696f70ba365", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Quotation Detail", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "attached_element_id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "09972220-37f0-4e7e-b9cf-796772aa4472", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Pricing Document", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "pricing_document", "name": "pricing_document", "type": "tag", "class": "s4-form-control text-size-17", "label": "Pricing Document", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Service Agreement ID", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "service_agreement_id", "name": "service_agreement_id", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Service Agreement Id", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "a9b07900-98d4-4803-b985-167c8b202b32", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "type": "label", "class": "bold", "label": "Label", "value": "System Handle", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "a9b07900-98d4-4803-b985-167c8b202b32"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "system_handle", "name": "system_handle", "type": "tag", "class": "s4-form-control text-size-17", "label": "System Handle", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "a9b07900-98d4-4803-b985-167c8b202b32"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Equipment Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "equipment_address", "name": "equipment_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Equipment Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Software Update Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_address", "name": "software_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Hardware Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_contact", "name": "hardware_contact", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Software Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_contact", "name": "software_contact", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_phone", "name": "hardware_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_phone", "name": "software_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "736ac1e0-52c8-4f80-bc81-7bae72e10102", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Coverage Period", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "coverage_period_from", "name": "coverage_period_from", "type": "tag", "class": "s4-form-control text-size-17", "label": "Coverage Period From", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "type": "label", "class": "text-size-17", "label": "Label", "value": "to", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "coverage_period_to", "name": "coverage_period_to", "type": "tag", "class": "s4-form-control text-size-17", "label": "Coverage Period To", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "be234931-7998-40c4-9b23-16b113c57e21", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12 mt-2", "controls": [{"id": "91df5263-00d6-40c5-b327-00765c4fc8b2", "src": null, "name": "l-1", "type": "label", "class": "bold", "label": "Label", "value": "Service Level (s):", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "service_levels", "name": "service_levels", "show": true, "type": "tag", "class": "s4-form-control", "label": "Service Level", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "be234931-7998-40c4-9b23-16b113c57e21"}], "position": 1}], "class": "single-column field-dragger", "order": 4, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "first_page": [{"id": "2534cf3e-7155-4fdf-bb2a-d830e29bb4d5", "name": "Single Column", "child": [{"id": "846216fa-eeaf-44d0-8d95-8e93168816f1", "class": "col-lg-12 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "text-right", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "846216fa-eeaf-44d0-8d95-8e93168816f1", "attached_element_id": "2534cf3e-7155-4fdf-bb2a-d830e29bb4d5"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "205bb944-4333-4add-baa7-5ae17bf238a9", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "44ee34b7-4ab7-44da-9134-9994259f85d0", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "8c74b0d3-f439-4383-a442-e4386fb957d9", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12", "controls": [{"id": "ae1e9dc9-9458-4172-a2b7-6086a29ce1b0", "css": null, "src": null, "name": "h-1", "show": true, "type": "hr", "class": "blue", "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "8c74b0d3-f439-4383-a442-e4386fb957d9"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "a8661be5-1b81-43ad-a614-2fefc2db80eb", "name": "Single Column", "child": [{"id": "88647d86-d061-43d5-9078-1e232b5ea47e", "class": "col-lg-12", "controls": [{"id": "0b2402a3-38de-47f8-a0f6-83b7c3cc3764", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Quotation Summary", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "88647d86-d061-43d5-9078-1e232b5ea47e", "attached_element_id": "a8661be5-1b81-43ad-a614-2fefc2db80eb"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "e1b21561-9910-4375-b324-bbee46c8bbf4", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "quotation_number", "name": "quotation_number", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "RFQ Number", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "344e5c52-2caf-4376-a4b3-915ca7a12965", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "css": "font-size: 12px", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Valid Until:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "valid_until", "name": "valid_until", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "Quotation Closing Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support Start Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support End Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "List Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "list_price", "name": "list_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total List Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a7623070-c87b-428f-a894-87ccfcb909c2", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Applicable Discounts:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "applicable_discounts", "name": "applicable_discounts", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total Discounts", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Final Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "final_price", "name": "final_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Final Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}, {"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "(All prices are excluding applicable taxes)", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Payment Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "6467a459-8603-44de-8f51-b4ce915ce8db", "src": null, "name": "l-1", "show": true, "type": "label", "class": "text-nowrap", "label": "Label", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "c174ce4d-8e57-430a-961f-b1c1933d4127", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Invoicing Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "c174ce4d-8e57-430a-961f-b1c1933d4127"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "invoicing_terms", "name": "invoicing_terms", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Invoicing Terms", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "c174ce4d-8e57-430a-961f-b1c1933d4127"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "b1bf7d16-b033-4e0d-a646-d267e46f0886", "name": "Single Column", "child": [{"id": "88647d86-d061-43d5-9078-1e232b5ea47e", "class": "col-lg-12", "controls": [{"id": "d8413f5a-86f4-4228-a282-be3dd3699fa0", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Order Authorisation", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "88647d86-d061-43d5-9078-1e232b5ea47e", "attached_element_id": "b1bf7d16-b033-4e0d-a646-d267e46f0886"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "2027b6f8-5391-406a-846a-a9d6b3c2dff9", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Full name", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "2027b6f8-5391-406a-846a-a9d6b3c2dff9"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "db604244-68f5-49bd-9a6b-6a7a93f83c2d", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Order Number (if applicable)", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "db604244-68f5-49bd-9a6b-6a7a93f83c2d"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "899208a2-766b-43b5-8adc-8d384e5999d8", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Date", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "899208a2-766b-43b5-8adc-8d384e5999d8"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "8322df1d-bf7c-4c0f-984b-9f925035eabe", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Signature", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "8322df1d-bf7c-4c0f-984b-9f925035eabe"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "9c378e04-49eb-472d-bead-5a02d628303b", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Contact Us", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "9c378e04-49eb-472d-bead-5a02d628303b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "eb4410df-3cb1-4e68-866a-f294658768b7", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Tel:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "0800 072 0950", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "5591650b-9c22-4f55-a286-916b64aa5588", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Email:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "gb@supportwarehouse.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "fef1dba7-386f-4959-bd7d-121de4a0cd54", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Visit:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "www.supportwarehouse.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}], "payment_page": [{"id": "7403b962-3dcd-43a9-b378-f52c616bbe9d", "name": "Three Column", "child": [{"id": "62d78c91-3073-4fa4-95fb-d19cb52610b3", "class": "col-lg-6", "controls": [], "position": 1}, {"id": "271be802-e645-4b7f-863c-efb911a1089a", "class": "col-lg-3 border-right", "controls": [{"id": "company_logo_x3", "src": "http://68.183.35.153/easyQuote-API/public/storage/images/companies/ypx1Wm6EUpFkEq3bqhKqTCBDpSFUVlsmhzLA1yob@x3.png", "name": "company_logo_x3", "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "271be802-e645-4b7f-863c-efb911a1089a", "attached_element_id": "7403b962-3dcd-43a9-b378-f52c616bbe9d"}], "position": 2}, {"id": "af8777bb-6c28-4d30-86b3-e3ab425d8b8b", "class": "col-lg-3", "controls": [{"id": "vendor_logo_x3", "src": "http://68.183.35.153/easyQuote-API/public/storage/images/vendors/mxOrsQXs5a0fRSdK4h0hPSY8rZK1md2dYo8HB6af@x3.png", "name": "vendor_logo_x3", "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "af8777bb-6c28-4d30-86b3-e3ab425d8b8b", "attached_element_id": "7403b962-3dcd-43a9-b378-f52c616bbe9d"}], "position": 3}], "class": "three-column field-dragger", "order": 4, "controls": [], "is_field": false, "droppable": false, "decoration": "1+2"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "vendor_name", "css": null, "name": "vendor_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Vendor Full Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "eb1e8eb2-2616-455c-aa10-52578736687e"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "c83dff46-b6ef-4553-bc69-775d3694a487", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "54682e57-49d0-47c0-bb51-44233fa41438", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "13612e0d-7a90-420f-88cc-73ea17046865", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "087c4ed7-f16b-4250-b025-69ec72801d2f", "src": null, "name": "h-1", "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "13612e0d-7a90-420f-88cc-73ea17046865"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "baadd19a-a0b7-491c-930b-e1325653c3ce", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "Payment Schedule From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "To", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}]}', true),
        ]);

        $template->vendors()->sync(Vendor::query()->limit(2)->get());

        $country = Country::query()->first();
        $vendor = Vendor::query()->first();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'submitted_at' => now(),
        ]);

        $wwQuote->activeVersion->update([
            'use_groups' => true,
            'quote_template_id' => $template->getKey(),
            'multi_year_discount_id' => $multiYearDiscount->getKey(),
            'pricing_document' => Str::random(20),
            'service_agreement_id' => Str::random(20),
            'system_handle' => Str::random(20),
            'sort_rows_column' => 'vendor_short_code',
            'sort_rows_direction' => 'asc',
        ]);

        $machineAddress = factory(Address::class)->create([
            'address_type' => 'Machine',
        ]);

        $softwareAddress = factory(Address::class)->create([
            'address_type' => 'Software',
        ]);

        $hardwareContact = factory(Contact::class)->create([
            'contact_type' => 'Hardware',
        ]);

        $softwareContact = factory(Contact::class)->create([
            'contact_type' => 'Software',
        ]);

        $wwQuote->activeVersion->addresses()->sync([$machineAddress->getKey(), $softwareAddress->getKey()]);
        $wwQuote->activeVersion->contacts()->sync([$hardwareContact->getKey(), $softwareContact->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 5)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'is_selected' => true,
        ]);

        /** @var WorldwideQuoteAssetsGroup[] $groupsOfAssets */
        $groupsOfAssets = factory(WorldwideQuoteAssetsGroup::class, 2)->create([
            'worldwide_quote_version_id' => $wwQuote->activeVersion->getKey(),
        ]);

        $groupsOfAssets[0]->assets()->sync($assets->slice(0, 2));
        $groupsOfAssets[1]->assets()->sync($assets->slice(2, 3));

        $expectedFileName = $wwQuote->quote_number.'.pdf';

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");

        $fakeStorage = Storage::persistentFake();

        $fakeStorage->put($expectedFileName, $response->getContent());
    }

    /**
     * Test an ability to mark an existing Worldwide Pack Quote as active.
     *
     * @return void
     */
    public function testCanMarkWorldwidePackQuoteAsActive()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK, 'activated_at' => null]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/activate')
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark an existing Worldwide Pack Quote as inactive.
     *
     * @return void
     */
    public function testCanMarkWorldwidePackQuoteAsInactive()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK, 'activated_at' => now()]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/deactivate')
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to view price summary of pack quote after country margin & tax.
     *
     * @return void
     */
    public function testCanViewPriceSummaryOfPackQuoteAfterCountryMarginAndTax()
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'activated_at' => now(),
        ]);

        $quote->activeVersion->update(['buy_price' => 2000.00, 'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id')]);

        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_price' => 2000,
            'price' => 3000.00,
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'is_selected' => true,
        ]);

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/pack/country-margin-tax-price-summary', [
            'margin_value' => 10.00,
            'tax_value' => 10.00,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'price_summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_margin',
                ],
            ])
            ->assertJson([
                'price_summary' => [
                    'total_price' => '3000.00',
                    'buy_price' => '2000.00',
                    'final_total_price' => '3539.41',
                    'final_margin' => '43.33',
                ],
            ]);
    }

    /**
     *  Test an ability to view price summary of pack quote after custom discount.
     *
     * @return void
     */
    public function testCanViewPriceSummaryOfPackQuoteAfterCustomDiscount()
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'activated_at' => now()
//            'tax_value' => 0.00,
        ]);

        $quote->activeVersion->update([
            'buy_price' => 2000.00,
            'tax_value' => 10.00,
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_price' => 2000,
            'price' => 3000.00,
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'is_selected' => true,
        ]);

        $this->authenticateApi();

//        dump((2727.27 - 2000) / 2727.27 * 100);

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/pack/discounts-price-summary', [
            'custom_discount' => 10.00,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'price_summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_margin',
                ],
            ])
            ->assertJson([
                'price_summary' => [
                    'total_price' => '3000.00',
                    'buy_price' => '2000.00',
                    'final_total_price' => '2618.70',
                    'applicable_discounts_value' => '391.30',
                    'final_margin' => '23.33',
                ],
            ]);
    }

    /**
     * Test an ability to view price summary of pack quote after predefined discounts.
     *
     * @return void
     */
    public function testCanViewPriceSummaryOfPackQuoteAfterPredefinedDiscounts()
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
            'activated_at' => now(),
//            'tax_value' => 0.00,
        ]);

        $quote->activeVersion->update([
            'buy_price' => 2000.00,
            'tax_value' => 10.00,
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_price' => 2000.00,
            'price' => 3000.00,
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'is_selected' => true,
        ]);

        $this->authenticateApi();

        $snDiscount = factory(SND::class)->create(['value' => 5.00]);

//        dump((2850.00 - 2000) / 2850.00 * 100);

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/pack/discounts-price-summary', [
            'predefined_discounts' => [
                'sn_discount' => $snDiscount->getKey(),
            ],
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'price_summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_margin',
                ],
            ])
            ->assertJson([
                'price_summary' => [
                    'total_price' => '3000.00',
                    'buy_price' => '2000.00',
                    'final_total_price' => '2800.70',
                    'final_total_price_excluding_tax' => '2790.70',
                    'margin_after_sn_discount' => '28.33',
                    'final_margin' => '28.33',
                ],
            ]);
    }

    /**
     * Test an ability to mark worldwide pack quote as 'dead'.
     *
     * @return void
     */
    public function testCanMarkWorldwidePackQuoteAsDead()
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $this->authenticateApi();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/dead', [
            'status_reason' => $statusReason = 'End of Service (Obsolete)',
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason,
            ]);
    }

    /**
     * Test an ability to mark worldwide contract quote as 'alive'.
     *
     * @return void
     */
    public function testCanMarkWorldwidePackQuoteAsAlive()
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $this->authenticateApi();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/dead', [
            'status_reason' => $statusReason = 'End of Service (Obsolete)',
        ])
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason,
            ]);

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/restore-from-dead')
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJson([
                'status' => 1,
                'status_reason' => null,
            ]);
    }

    /**
     * Test an ability to switch active version of pack quote.
     *
     * @return void
     */
    public function testCanSwitchActiveVersionOfPackQuote()
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $version = factory(WorldwideQuoteVersion::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->authenticateApi();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/versions/'.$version->getKey())
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
            ])
            ->assertJson([
                'active_version_id' => $version->getKey(),
            ]);
    }

    /**
     * Test an ability to delete an existing version of pack quote.
     *
     * @return void
     */
    public function testCanDeleteExistingVersionOfPackQuote()
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $version = factory(WorldwideQuoteVersion::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=versions')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
                'versions' => [
                    '*' => [
                        'id', 'worldwide_quote_id', 'user_id', 'user_version_sequence_number', 'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('versions'));

        $this->deleteJson('api/ww-quotes/'.$quote->getKey().'/versions/'.$version->getKey())
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=versions')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
                'versions' => [
                    '*' => [
                        'id', 'worldwide_quote_id', 'user_id', 'user_version_sequence_number', 'updated_at',
                    ],
                ],
            ])
            ->assertJsonMissing([
                'versions' => [
                    ['id' => $version->getKey()],
                ],
            ]);

        $this->assertCount(1, $response->json('versions'));
    }

    /**
     * Test an ability to delete an active version of pack quote.
     *
     * @return void
     */
    public function testCanNotDeleteActiveVersionOfPackQuote()
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_PACK]);

        $version = factory(WorldwideQuoteVersion::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=versions')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
                'versions' => [
                    '*' => [
                        'id', 'worldwide_quote_id', 'user_id', 'user_version_sequence_number', 'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('versions'));

        $this->deleteJson('api/ww-quotes/'.$quote->getKey().'/versions/'.$quote->activeVersion->getKey())
//            ->dump()
            ->assertForbidden();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=versions')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
                'versions' => [
                    '*' => [
                        'id', 'worldwide_quote_id', 'user_id', 'user_version_sequence_number', 'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('versions'));

        $this->assertTrue(value(function () use ($response, $version) {
            foreach ($response->json('versions') as $versionFromResponse) {
                if ($versionFromResponse['id'] === $version->getKey()) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Test an ability to explicitly create a new version of pack worldwide quote.
     *
     * @return void
     */
    public function testCanCreateNewVersionOfPackWorldwideQuote()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/versions')
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'version_name',
                'is_active_version',
                'created_at',
                'updated_at',
            ]);

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'acting_user_is_version_owner',
                'active_version_id',
                'active_version_user_id',
            ])
            ->assertJsonFragment([
                'acting_user_is_version_owner' => true,
                'active_version_id' => $response->json('id'),
                'active_version_user_id' => $this->app['auth.driver']->id(),
            ]);
    }

    /**
     * Test an ability to explicitly create a new version of pack worldwide quote.
     *
     * @return void
     */
    public function testCanCreateNewVersionOfPackWorldwideQuoteFromSpecifiedVersion()
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/versions')
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'version_name',
                'is_active_version',
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=versions')
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'active_version_id',
                'versions' => [
                    '*' => [
                        'id',
                    ],
                ],
            ]);

        $nonActiveVersionID = value(function () use ($response) {
            $activeVersionID = $response->json('active_version_id');

            foreach ($response->json('versions.*.id') as $id) {
                if ($id !== $activeVersionID) {
                    return $id;
                }
            }

            return null;
        });

        $this->assertNotEmpty($nonActiveVersionID);

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/versions/'.$nonActiveVersionID)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'version_name',
                'is_active_version',
                'created_at',
                'updated_at',
            ]);

        $this->getJson('api/ww-quotes/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'acting_user_is_version_owner',
                'active_version_id',
                'active_version_user_id',
            ])
            ->assertJsonFragment([
                'acting_user_is_version_owner' => true,
                'active_version_id' => $response->json('id'),
                'active_version_user_id' => $this->app['auth.driver']->id(),
            ]);
    }

    /**
     * Test an ability to affect on the pack quote data by updating of opportunity entity.
     *
     * @return void
     */
    public function testCanAffectOnPackQuoteDataByUpdatingOfOpportunity()
    {
        /** @var Opportunity $opportunity */
        $opportunity = factory(Opportunity::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        /** @var \App\Models\Quote\WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
            'contract_type_id' => CT_PACK,
        ]);

        $quote->activeVersion->quoteCurrency()->disassociate();
        $quote->activeVersion->buyCurrency()->disassociate();
        $quote->activeVersion->save();

        $this->authenticateApi();

        $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query([
                'include' => [
                    'buy_currency', 'quote_currency',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_currency_id',
                'buy_currency_id',
            ]);

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'contract_type_id' => $opportunity->contract_type_id,
            'opportunity_start_date' => now()->toDateString(),
            'opportunity_end_date' => now()->addYear()->toDateString(),
            'opportunity_closing_date' => now()->addYear()->toDateString(),
            'purchase_price_currency_code' => 'AUD',
            'is_contract_duration_checked' => false,
        ])
            ->assertOk();

        $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query([
                'include' => [
                    'buy_currency', 'quote_currency',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_currency_id',
                'quote_currency' => [
                    'id',
                    'name',
                    'code',
                ],
                'buy_currency_id',
                'buy_currency' => [
                    'id',
                    'name',
                    'code',
                ],
            ])
            ->assertJson([
                'quote_currency' => [
                    'code' => 'AUD',
                ],
            ])
            ->assertJson([
                'buy_currency' => [
                    'code' => 'AUD',
                ],
            ]);

        /** Currencies should stay the same, once they are set **/

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'contract_type_id' => $opportunity->contract_type_id,
            'opportunity_start_date' => now()->toDateString(),
            'opportunity_end_date' => now()->addYear()->toDateString(),
            'opportunity_closing_date' => now()->addYear()->toDateString(),
            'purchase_price_currency_code' => 'USD',
            'is_contract_duration_checked' => false,
        ])
            ->assertOk();

        $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query([
                'include' => [
                    'buy_currency', 'quote_currency',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'quote_currency_id',
                'quote_currency' => [
                    'id',
                    'name',
                    'code',
                ],
                'buy_currency_id',
                'buy_currency' => [
                    'id',
                    'name',
                    'code',
                ],
            ])
            ->assertJson([
                'quote_currency' => [
                    'code' => 'AUD',
                ],
            ])
            ->assertJson([
                'buy_currency' => [
                    'code' => 'AUD',
                ],
            ]);
    }

    /**
     * Test an ability to create a new group of the pack quote assets.
     *
     * @return void
     */
    public function testCanCreateGroupOfPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        $groupData = [
            'group_name' => Str::random(40),
            'search_text' => Str::random(40),
            'assets' => $assets->random(2)->modelKeys(),
        ];

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets-groups', $groupData)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_version_id',
                'assets_sum',
                'assets_count',
                'assets' => [
                    '*' => [
                        'id', 'is_customer_exclusive_asset',
                    ],
                ],
                'group_name',
                'search_text',
                'is_selected',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotNull($response->json('assets_sum'));
        $this->assertNotNull($response->json('assets_count'));
    }

    /**
     * Test an ability to lookup pack quote assets.
     *
     * @return void
     */
    public function testCanLookupPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        $assets->push(factory(WorldwideQuoteAsset::class)->create([

            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'sku' => '818208-B21',

        ]));

        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create([
            'worldwide_quote_version_id' => $quote->activeVersion->getKey(),
        ]);

        $assetsGroup->assets()->sync($assets->modelKeys());

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/assets-lookup', [
            'input' => '818208-B21, CZJ8170VHN',
            'assets_group_id' => $assetsGroup->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'sku',
                    'service_sku',
                    'product_name',
                    'serial_no',
                    'expiry_date',
                    'price',
                    'service_level_description',
                    'is_selected',
                    'vendor_short_code',
                    'machine_address_string',
                    'buy_currency_code',
                    'is_customer_exclusive_asset',
                    'is_warranty_checked',
                ],
            ]);
    }

    /**
     * Test an ability to view the specified group of pack quote assets.
     *
     * @return void
     */
    public function testCanViewGroupOfPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        /** @var WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create(['worldwide_quote_version_id' => $quote->activeVersion->getKey()]);

        $assetsGroup->assets()->sync($assets);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'/assets-groups/'.$assetsGroup->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_version_id',
                'assets' => [
                    '*' => [
                        'id',
                        'vendor_id',
                        'machine_address_id',
                        'buy_currency_id',
                        'is_selected',
                        'is_customer_exclusive_asset',
                        'country',
                        'serial_no',
                        'sku',
                        'service_sku',
                        'product_name',
                        'expiry_date',
                        'service_level_description',
                        'service_level_data',
                        'price',
                        'original_price',
                        'exchange_rate_value',
                        'exchange_rate_margin',
                        'vendor_short_code',
                        'buy_currency_code',
                        'machine_address_string',
                        'is_warranty_checked',
                    ],
                ],
                'assets_sum',
                'assets_count',
                'group_name',
                'search_text',
                'is_selected',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotNull($response->json('assets_sum'));
        $this->assertNotNull($response->json('assets_count'));
    }

    /**
     * Test an ability to update the existing group of pack quote assets.
     *
     * @return void
     */
    public function testCanUpdateGroupOfPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        /** @var WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create(['worldwide_quote_version_id' => $quote->activeVersion->getKey()]);

        $assetsGroup->assets()->sync($assets);

        $groupData = [
            'group_name' => Str::random(40),
            'search_text' => Str::random(40),
            'assets' => $assets->random(2)->modelKeys(),
        ];

        $response = $this->patchJson('api/ww-quotes/'.$quote->getKey().'/assets-groups/'.$assetsGroup->getKey(), $groupData)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_version_id',
                'assets' => [
                    '*' => [
                        'id', 'is_customer_exclusive_asset', 'is_warranty_checked',
                    ],
                ],
                'assets_sum',
                'assets_count',
                'group_name',
                'search_text',
                'is_selected',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotNull($response->json('assets_sum'));
        $this->assertNotNull($response->json('assets_count'));
    }


    /**
     * Test an ability to delete the existing group of pack quote assets.
     *
     * @return void
     */
    public function testCanDeleteGroupOfPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        /** @var WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create(['worldwide_quote_version_id' => $quote->activeVersion->getKey()]);

        $assetsGroup->assets()->sync($assets);

        $this->deleteJson('api/ww-quotes/'.$quote->getKey().'/assets-groups/'.$assetsGroup->getKey())
//            ->dump()
            ->assertNoContent();
    }

    /**
     * Test an ability to move rows between groups of pack quote assets.
     *
     * @return void
     */
    public function testCanMoveRowsBetweenGroupsOfPackQuoteAssets()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $assets = factory(WorldwideQuoteAsset::class, 10)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        /** @var WorldwideQuoteAssetsGroup $outputAssetsGroup */
        $outputAssetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create(['worldwide_quote_version_id' => $quote->activeVersion->getKey()]);

        $outputAssetsGroup->assets()->sync($assets);

        /** @var WorldwideQuoteAssetsGroup $outputAssetsGroup */
        $inputAssetsGroup = factory(WorldwideQuoteAssetsGroup::class)->create(['worldwide_quote_version_id' => $quote->activeVersion->getKey()]);

        $this->putJson('api/ww-quotes/'.$quote->getKey().'/assets-groups', [
            'output_assets_group_id' => $outputAssetsGroup->getKey(),
            'assets' => $assets->modelKeys(),
            'input_assets_group_id' => $inputAssetsGroup->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'output_assets_group' => [
                    'id',
                    'worldwide_quote_version_id',
                    'assets',
                    'assets_sum',
                    'assets_count',
                    'group_name',
                    'search_text',
                    'is_selected',
                    'created_at',
                    'updated_at',
                ],
                'input_assets_group' => [
                    'id',
                    'worldwide_quote_version_id',
                    'assets',
                    'assets_sum',
                    'assets_count',
                    'group_name',
                    'search_text',
                    'is_selected',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // TODO: test that rows are moved to another group.
    }

    /**
     * Test an ability to see recalculated selling price of assets when quote currency is changed.
     *
     * @return void
     */
    public function testCanSeeRecalculatedSellingPriceOfAssetsWhenQuoteCurrencyIsChanged(): void
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_PACK,
        ]);

        $quote->activeVersion->update([
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
        ]);

        $asset = factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_price' => 100, // buy price
            'buy_price_margin' => 20,
            'price' => 100, // selling price
            'exchange_rate_value' => 1,
            'exchange_rate_margin' => 0,
            'buy_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
        ]);

        $this->authenticateApi();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=assets')
            ->assertOk()
            ->assertJsonStructure([
                'assets' => [
                    '*' => [
                        'id', 'buy_price', 'buy_price_margin', 'price', 'exchange_rate_value', 'exchange_rate_margin',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('assets'));
        $this->assertSame(100, $response->json('assets.0.price'));
        $this->assertEquals(1, $response->json('assets.0.exchange_rate_value'));

        $exchangeRate = ExchangeRate::query()->create([
            'currency_code' => 'USD',
            'exchange_rate' => 1.36,
            'base_currency' => 'GBP',
            'date' => $quote->created_at->toDateString(),
        ]);

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/contacts', [
            'company_id' => Company::query()->where('type', 'Internal')->first()->getKey(),
            'quote_template_id' => factory(QuoteTemplate::class)->create()->getKey(),
            'quote_expiry_date' => now()->addYear()->toDateString(),
            'buy_price' => 100,
            'payment_terms' => '30 Days',
            'stage' => 'Assets Creation',
            'quote_currency_id' => Currency::query()->where('code', 'USD')->sole()->getKey(),
        ])
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=assets')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'assets' => [
                    '*' => [
                        'id', 'buy_price', 'buy_price_margin', 'price', 'exchange_rate_value', 'exchange_rate_margin',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('assets'));
        $this->assertEquals((100 + (100 * .2)) * 1.36, $response->json('assets.0.price'));
    }
}
