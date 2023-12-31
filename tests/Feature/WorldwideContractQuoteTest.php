<?php

namespace Tests\Feature;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\Note\Models\Note;
use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Template\Models\TemplateField;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunitySupplier;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webpatser\Uuid\Uuid;

/**
 * Class WorldwideQuoteTest.
 *
 * @group worldwide
 */
class WorldwideContractQuoteTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to initialize a new Contract Worldwide Quote.
     */
    public function testCanInitContractWorldwideQuote(): void
    {
        $this->authenticateApi();

        $opportunities = Opportunity::factory()->count(10)->create();

        foreach ($opportunities as $opportunity) {
            factory(OpportunitySupplier::class)->create([
                'opportunity_id' => $opportunity->getKey(),
            ]);
        }

        // Pick a random opportunity from the opportunities endpoint.
        $response = $this->getJson('api/opportunities')->assertOk();

        $opportunityId = $response->json('data.0.id');

        $this->assertNotEmpty($opportunityId);

        // Perform initialize Worldwide Quote request.
        $response = $this->postJson('api/ww-quotes', [
            'opportunity_id' => $opportunityId,
            'contract_type' => 'contract',
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

        $response = $this->getJson('api/ww-quotes/'.$response->json('id').'?include[]=worldwide_distributions.opportunity_supplier')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'worldwide_quote_id',
                        'opportunity_supplier' => [
                            'id', 'supplier_name',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('worldwide_distributions'));
        $this->assertNotEmpty($response->json('worldwide_distributions.0.opportunity_supplier'));
    }

    /**
     * Test opportunity suppliers synchronize with worldwide quote distributions.
     */
    public function testOpportunitySuppliersSynchronizeWithWorldwideQuoteDistributions(): void
    {
        $opportunity = Opportunity::factory()->create(['contract_type_id' => CT_CONTRACT]);

        $suppliers = factory(OpportunitySupplier::class, 2)->create(['opportunity_id' => $opportunity->getKey()]);

        $this->authenticateApi();

        $response = $this->postJson('api/ww-quotes',
            ['opportunity_id' => $opportunity->getKey(), 'contract_type' => 'contract'])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
            ]);

        $quoteKey = $response->json('id');

        $response = $this->getJson("api/ww-quotes/$quoteKey?include[]=worldwide_distributions")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'opportunity_id',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'opportunity_supplier_id',
                    ],
                ],
            ]);

        $this->assertEquals($opportunity->getKey(), $response->json('opportunity_id'));

        $this->assertCount(2, $response->json('worldwide_distributions'));

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'pipeline_id' => PL_WWDP,
            'contract_type_id' => CT_CONTRACT,
            'opportunity_start_date' => $this->faker->date(),
            'opportunity_end_date' => $this->faker->date(),
            'opportunity_closing_date' => $this->faker->date(),
            'suppliers_grid' => null,
            'is_contract_duration_checked' => false,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson("api/ww-quotes/$quoteKey?include[]=worldwide_distributions")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'opportunity_supplier_id',
                    ],
                ],
            ]);

        $this->assertEmpty($response->json('worldwide_distributions'));

        $supplierFieldValue = CustomFieldValue::factory()->for(
            CustomField::query()->where('field_name', 'opportunity_distributor1')->sole()
        )
            ->hasAttached(
                CustomFieldValue::factory()->for(
                    CustomField::query()->where('field_name', 'opportunity_country1')->sole(),
                ),
                relationship: 'allowedBy'
            )
            ->create();

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'pipeline_id' => PL_WWDP,
            'contract_type_id' => CT_CONTRACT,
            'opportunity_start_date' => $this->faker->date(),
            'opportunity_end_date' => $this->faker->date(),
            'opportunity_closing_date' => $this->faker->date(),
            'suppliers_grid' => [
                [
                    'country_name' => $supplierFieldValue->allowedBy->first()->field_value,
                    'supplier_name' => $supplierFieldValue->field_value,
                ],
            ],
            'is_contract_duration_checked' => false,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson("api/ww-quotes/$quoteKey?include[]=worldwide_distributions")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'opportunity_supplier_id',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions'));
    }

    /**
     * Test an ability to process worldwide quote import step.
     */
    public function testCanProcessImportStepOfWorldwideContractQuote(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create(
            [
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'distribution_currency_id' => Currency::query()->value('id'),
            ]
        );

        $opportunity = Opportunity::factory()->create();

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $template = factory(QuoteTemplate::class)->create();

        $quoteExpiryDate = now()->addMonth();

        $distributionsData = $wwDistributions->map(fn (WorldwideDistribution $distribution) => [
            'id' => $distribution->getKey(),
            'vendors' => Vendor::query()->limit(2)->pluck('id')->all(),
            'country_id' => Country::query()->value('id'),
            'distribution_currency_id' => Currency::query()->value('id'),
            'buy_currency_id' => Currency::query()->value('id'),
            'buy_price' => $this->faker->randomFloat(2, 1000, 10000),
            'calculate_list_price' => $this->faker->boolean,
            'distribution_expiry_date' => $this->faker->dateTimeBetween('+30 days', '+60 days')->format('Y-m-d'),
            'addresses' => [factory(Address::class)->create()->getKey()],
            'contacts' => [Contact::factory()->create()->getKey()],
            'distribution_currency_quote_currency_exchange_rate_value' => 1,
            'distribution_currency_quote_currency_exchange_rate_margin' => 7,
        ])->all();

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/import', $importStageData = [
            'company_id' => Company::query()->where('type', 'Internal')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'output_currency_id' => Currency::query()->value('id'),
            'quote_template_id' => $template->getKey(),
            'quote_expiry_date' => $quoteExpiryDate->toDateString(),
            'exchange_rate_margin' => 7,
            'worldwide_distributions' => $distributionsData,
            'payment_terms' => '30 Days',
            'are_end_user_addresses_available' => false,
            'are_end_user_contacts_available' => false,
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.contacts',
                    'worldwide_distributions.addresses',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'addresses' => [
                            '*' => ['id', 'is_default'],
                        ],
                        'contacts' => [
                            '*' => ['id', 'is_default'],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.addresses.0.is_default'));
        $this->assertCount(1, $response->json('worldwide_distributions.1.addresses'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.1.addresses.0.is_default'));

        $this->assertCount(1, $response->json('worldwide_distributions.0.contacts'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.contacts.0.is_default'));
        $this->assertCount(1, $response->json('worldwide_distributions.1.contacts'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.contacts.0.is_default'));

        $this->assertSame($importStageData['are_end_user_addresses_available'],
            $response->json('are_end_user_addresses_available'));
        $this->assertSame($importStageData['are_end_user_contacts_available'],
            $response->json('are_end_user_contacts_available'));

        $this->assertSame($distributionsData[0]['distribution_currency_quote_currency_exchange_rate_value'],
            $response->json('worldwide_distributions.0.distribution_currency_quote_currency_exchange_rate_value'));
        $this->assertSame($distributionsData[1]['distribution_currency_quote_currency_exchange_rate_value'],
            $response->json('worldwide_distributions.1.distribution_currency_quote_currency_exchange_rate_value'));

        $this->assertSame($distributionsData[0]['distribution_currency_quote_currency_exchange_rate_margin'],
            $response->json('worldwide_distributions.0.distribution_currency_quote_currency_exchange_rate_margin'));
        $this->assertSame($distributionsData[1]['distribution_currency_quote_currency_exchange_rate_margin'],
            $response->json('worldwide_distributions.1.distribution_currency_quote_currency_exchange_rate_margin'));

        $distributionsData[0]['id'] = $response->json('worldwide_distributions.0.id');
        $distributionsData[1]['id'] = $response->json('worldwide_distributions.1.id');

        $distributionsData[0]['addresses'][0] = $response->json('worldwide_distributions.0.addresses.0.id');
        $distributionsData[1]['addresses'][0] = $response->json('worldwide_distributions.1.addresses.0.id');

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/import', $importStageData = [
            'company_id' => Company::query()->where('type', 'Internal')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'output_currency_id' => Currency::query()->value('id'),
            'quote_template_id' => $template->getKey(),
            'quote_expiry_date' => $quoteExpiryDate->toDateString(),
            'exchange_rate_margin' => 7,
            'worldwide_distributions' => $distributionsData,
            'payment_terms' => '30 Days',
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

//        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.addresses')
        // //            ->dump()
//            ->assertOk()
//            ->assertJsonStructure([
//                'id',
//                'worldwide_distributions' => [
//                    '*' => [
//                        'id', 'addresses' => [
//                            '*' => ['id', 'is_default']
//                        ]
//                    ]
//                ]
//            ]);
//
//        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
//        $this->assertCount(1, $response->json('worldwide_distributions.1.addresses'));
//
//        $this->assertEquals($distributionsData[0]['addresses'][0]['id'], $response->json('worldwide_distributions.0.addresses.0.id'));
//        $this->assertEquals($distributionsData[1]['addresses'][0]['id'], $response->json('worldwide_distributions.1.addresses.0.id'));
    }

    /**
     * Test an ability to process worldwide quote import step.
     */
    public function testCanProcessImportStepOfWorldwideContractQuoteWithCustomFileDateFormat(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create(
            [
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'distribution_currency_id' => Currency::query()->value('id'),
            ]
        );

        $opportunity = Opportunity::factory()->create();

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $template = factory(QuoteTemplate::class)->create();

        $quoteExpiryDate = now()->addMonth();

        $distributionsData = $wwDistributions->map(fn (WorldwideDistribution $distribution) => [
            'id' => $distribution->getKey(),
            'vendors' => Vendor::query()->limit(2)->pluck('id')->all(),
            'country_id' => Country::query()->value('id'),
            'distribution_currency_id' => Currency::query()->value('id'),
            'buy_currency_id' => Currency::query()->value('id'),
            'buy_price' => $this->faker->randomFloat(2, 1000, 10000),
            'calculate_list_price' => $this->faker->boolean,
            'distribution_expiry_date' => $this->faker->dateTimeBetween('+30 days', '+60 days')->format('Y-m-d'),
            'addresses' => [factory(Address::class)->create()->getKey()],
            'contacts' => [Contact::factory()->create()->getKey()],
            'distribution_currency_quote_currency_exchange_rate_value' => 1,
            'distribution_currency_quote_currency_exchange_rate_margin' => 7,
            'file_date_format' => 'DD-MM-YYYY',
        ])->all();

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/import', $importStageData = [
            'company_id' => Company::query()->where('type', 'Internal')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'output_currency_id' => Currency::query()->value('id'),
            'quote_template_id' => $template->getKey(),
            'quote_expiry_date' => $quoteExpiryDate->toDateString(),
            'exchange_rate_margin' => 7,
            'worldwide_distributions' => $distributionsData,
            'payment_terms' => '30 Days',
            'are_end_user_addresses_available' => false,
            'are_end_user_contacts_available' => false,
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.contacts',
                    'worldwide_distributions.addresses',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'addresses' => [
                            '*' => ['id', 'is_default'],
                        ],
                        'contacts' => [
                            '*' => ['id', 'is_default'],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.addresses.0.is_default'));
        $this->assertCount(1, $response->json('worldwide_distributions.1.addresses'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.1.addresses.0.is_default'));

        $this->assertCount(1, $response->json('worldwide_distributions.0.contacts'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.contacts.0.is_default'));
        $this->assertCount(1, $response->json('worldwide_distributions.1.contacts'));
//        $this->assertEquals(1, $response->json('worldwide_distributions.0.contacts.0.is_default'));

        $this->assertSame($importStageData['are_end_user_addresses_available'],
            $response->json('are_end_user_addresses_available'));
        $this->assertSame($importStageData['are_end_user_contacts_available'],
            $response->json('are_end_user_contacts_available'));

        $this->assertSame($distributionsData[0]['distribution_currency_quote_currency_exchange_rate_value'],
            $response->json('worldwide_distributions.0.distribution_currency_quote_currency_exchange_rate_value'));
        $this->assertSame($distributionsData[1]['distribution_currency_quote_currency_exchange_rate_value'],
            $response->json('worldwide_distributions.1.distribution_currency_quote_currency_exchange_rate_value'));

        $this->assertSame($distributionsData[0]['distribution_currency_quote_currency_exchange_rate_margin'],
            $response->json('worldwide_distributions.0.distribution_currency_quote_currency_exchange_rate_margin'));
        $this->assertSame($distributionsData[1]['distribution_currency_quote_currency_exchange_rate_margin'],
            $response->json('worldwide_distributions.1.distribution_currency_quote_currency_exchange_rate_margin'));

        $distributionsData[0]['id'] = $response->json('worldwide_distributions.0.id');
        $distributionsData[1]['id'] = $response->json('worldwide_distributions.1.id');

        $distributionsData[0]['addresses'][0] = $response->json('worldwide_distributions.0.addresses.0.id');
        $distributionsData[1]['addresses'][0] = $response->json('worldwide_distributions.1.addresses.0.id');

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/import', $importStageData = [
            'company_id' => Company::query()->where('type', 'Internal')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'output_currency_id' => Currency::query()->value('id'),
            'quote_template_id' => $template->getKey(),
            'quote_expiry_date' => $quoteExpiryDate->toDateString(),
            'exchange_rate_margin' => 7,
            'worldwide_distributions' => $distributionsData,
            'payment_terms' => '30 Days',
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

//        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.addresses')
        // //            ->dump()
//            ->assertOk()
//            ->assertJsonStructure([
//                'id',
//                'worldwide_distributions' => [
//                    '*' => [
//                        'id', 'addresses' => [
//                            '*' => ['id', 'is_default']
//                        ]
//                    ]
//                ]
//            ]);
//
//        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
//        $this->assertCount(1, $response->json('worldwide_distributions.1.addresses'));
//
//        $this->assertEquals($distributionsData[0]['addresses'][0]['id'], $response->json('worldwide_distributions.0.addresses.0.id'));
//        $this->assertEquals($distributionsData[1]['addresses'][0]['id'], $response->json('worldwide_distributions.1.addresses.0.id'));
    }

    /**
     * Test an ability to view a state of worldwide quote with includes.
     */
    public function testCanViewStateOfWorldwideContractQuote(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $opportunity = Opportunity::factory()->create(['contract_type_id' => CT_CONTRACT]);

        $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT, 'opportunity_id' => $opportunity->getKey(),
        ]);

        $wwQuote->activeVersion->update(['quote_template_id' => $template->getKey()]);

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

        /** @var \App\Domain\Worldwide\Models\WorldwideDistribution[]|Collection $distributorQuotes */
        $distributorQuotes = factory(WorldwideDistribution::class, 2)->create(
            [
                'opportunity_supplier_id' => $opportunitySupplier->getKey(),
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'buy_price' => mt_rand(500, 2000),
            ]
        );

        $templateFields = TemplateField::query()
            ->where('is_system', true)
            ->whereIn('name', $this->app['config']['quote-mapping.worldwide_quote.fields'])
            ->pluck('id', 'name');

        $distributorQuotes->each(function (WorldwideDistribution $distributorQuote) use (
            $templateFields,
            $opportunity,
            $vendor
        ) {
            $distributorQuote->vendors()->sync($vendor);

            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);

            $distributorQuote->update(['distributor_file_id' => $distributorFile->getKey()]);

            $mappedRows = factory(MappedRow::class, 2)->create([
                'quote_file_id' => $distributorFile->getKey(), 'is_selected' => true,
            ]);

            $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $distributorQuote->getKey()]);
            $rowsGroup->rows()->sync($mappedRows->modelKeys());

            $distributorQuote->templateFields()->sync($templateFields);

            $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

            $distributorQuote->opportunitySupplier()->associate($opportunitySupplier)->save();

            $snDiscount = factory(SND::class)->create();

            $distributorQuote->snDiscount()->associate($snDiscount)->save();
        });

        /** @var \App\Domain\Asset\Models\Asset $sameAsset */
        $sameAsset = factory(Asset::class)->create([
            'serial_number' => $distributorQuotes[0]->mappedRows[0]->serial_no,
            'product_number' => $distributorQuotes[0]->mappedRows[0]->product_no,
        ]);

        $sameAsset->companies()->sync(Company::factory()->create());

        $query = Arr::query([
            'include' => [
                'worldwide_customer',
                'opportunity',
                'opportunity.primary_account',
                'opportunity.primary_account_contact',
                'opportunity.end_user',
                'opportunity.account_manager',
                'summary',
                'quote_template',
                'company.logo',
                'worldwide_distributions',
                'worldwide_distributions.distributor_file',
                'worldwide_distributions.schedule_file',
                'worldwide_distributions.vendors',
                'worldwide_distributions.vendors.logo',
                'worldwide_distributions.country',
                'worldwide_distributions.country.flag',
                'worldwide_distributions.country_margin',
                'worldwide_distributions.distribution_currency',
                'worldwide_distributions.mapping',
                'worldwide_distributions.mapping_row',
                'worldwide_distributions.mapped_rows.machine_address.country',
                'worldwide_distributions.rows_groups',
                'worldwide_distributions.rows_groups.rows',
                'worldwide_distributions.rows_groups.rows.machine_address.country',
                'worldwide_distributions.summary',
                'worldwide_distributions.applicable_discounts',
                'worldwide_distributions.predefined_discounts',
            ],
        ]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey()."?$query")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'opportunity_id',
                'quote_template_id',
                'company_id',
                'quote_currency_id',
                'output_currency_id',
                'exchange_rate_margin',
                'exchange_rate_margin',
                'actual_exchange_rate',
                'target_exchange_rate',
                'completeness',
                'stage',
                'quote_expiry_date',
                'closing_date',
//                'checkbox_status',
                'closing_date',
                'additional_notes',
                'created_at',
                'updated_at',
                'quote_template' => [
                    'id', 'name', 'form_data',
                ],
                'summary' => [
                    'total_price', 'final_total_price', 'applicable_discounts_value', 'buy_price', 'margin_percentage',
                    'final_margin',
                ],

                'margin_value',
                'quote_type',
                'margin_method',

                'tax_value',

                'opportunity' => [
                    'id',
//                    'primary_account_id',
                    'primary_account' => [
                        'id', 'name', 'short_code', 'name',
                    ],
//                    'primary_account_contact_id',
                    'primary_account_contact' => [
                        'id',
                    ],

                    'end_user' => [
                        'id', 'name', 'short_code', 'name',
                    ],
//                    'account_manager_id',
                    'account_manager' => [
                        'id',
                        'email',
                        'first_name',
                        'middle_name',
                        'last_name',
                    ],
                    'project_name',
                ],
                'worldwide_distributions' => [
                    '*' => [
                        'worldwide_quote_id',
                        'distributor_file_id',
                        'schedule_file_id',
                        'country_id',
                        'distribution_currency_id',
                        'distribution_currency',
                        'distribution_expiry_date',

                        'vendors' => [
                            '*' => [
                                'id', 'name', 'short_code', 'logo',
                            ],
                        ],
                        'country' => [
                            'id', 'iso_3166_2', 'name', 'flag',
                        ],

                        'mapping' => [
                            '*' => [
                                'importable_column_id',
                                'template_field_id',
                                'sort',
                                'template_field_name',
                                'template_field_header',
                                'is_editable',
                                'is_default_enabled',
                                'is_preview_visible',
                                'is_mapping_visible',
                                'is_required',
                            ],
                        ],
                        'mapping_row',
                        'mapped_rows' => [
                            '*' => [
                                'id',
                                'is_customer_exclusive_asset',
                                'contract_duration',
                                'contract_duration_months',
                                'machine_address_id',
                                'machine_address_string',
                            ],
                        ],

                        'sort_rows_column',
                        'sort_rows_direction',

                        'sort_rows_groups_column',
                        'sort_rows_groups_direction',

                        'margin_value',
                        'quote_type',
                        'margin_method',

                        'tax_value',

                        'rows_groups' => [
                            '*' => [
                                'id',
                                'group_name',
                                'search_text',
                                'rows_count',
                                'rows_sum',
                                'is_selected',
                                'created_at',
                                'updated_at',
                                'rows' => [
                                    '*' => [
                                        'id',
                                        'machine_address_id',
                                        'machine_address_string',
                                        'product_no',
                                        'description',
                                        'serial_no',
                                        'date_from',
                                        'date_to',
                                        'qty',
                                        'price',
                                        'pricing_document',
                                        'system_handle',
                                        'searchable',
                                        'service_level_description',
                                        'is_selected',
                                        'is_customer_exclusive_asset',
                                    ],
                                ],
                            ],
                        ],
                        'data',

                        'custom_discount',
                        'margin_percentage_after_custom_discount',

                        'summary' => [
                            'total_price', 'final_total_price', 'applicable_discounts_value', 'buy_price',
                            'margin_percentage', 'final_margin',
                        ],

                        'buy_price',

                        'calculate_list_price',
                        'use_groups',

                        'pricing_document',
                        'service_agreement_id',
                        'system_handle',
                        'additional_details',
                        'additional_notes',
                        'checkbox_status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $duplicatedAssetCount = 0;

        foreach (Arr::collapse($response->json('worldwide_distributions.*.mapped_rows')) as $row) {
            if ($row['serial_no'] === $sameAsset->serial_number && $row['product_no'] === $sameAsset->product_number) {
                $this->assertFalse($row['is_customer_exclusive_asset']);
                $this->assertArrayHasKey('owned_by_customer', $row);
                $this->assertArrayHasKey('permissions', $row['owned_by_customer']);
                $this->assertArrayHasKey('view', $row['owned_by_customer']['permissions']);
                $this->assertArrayHasKey('update', $row['owned_by_customer']['permissions']);
                $this->assertArrayHasKey('delete', $row['owned_by_customer']['permissions']);
                ++$duplicatedAssetCount;
            } else {
                $this->assertTrue($row['is_customer_exclusive_asset']);
            }
        }

        $this->assertSame(1, $duplicatedAssetCount);

        $mapping = Arr::pluck($response->json('worldwide_distributions.0.mapping'), 'is_required',
            'template_field_name');

        $this->assertSame('machine_address',
            $response->json('worldwide_distributions.0.mapping.0.template_field_name'));

        $this->assertArrayHasKey('machine_address', $mapping);
        $this->assertArrayHasKey('product_no', $mapping);
        $this->assertArrayHasKey('service_sku', $mapping);
        $this->assertArrayHasKey('description', $mapping);
        $this->assertArrayHasKey('serial_no', $mapping);
        $this->assertArrayHasKey('date_from', $mapping);
        $this->assertArrayHasKey('date_to', $mapping);
        $this->assertArrayHasKey('qty', $mapping);
        $this->assertArrayHasKey('price', $mapping);
        $this->assertArrayHasKey('searchable', $mapping);
        $this->assertArrayHasKey('service_level_description', $mapping);

        $this->assertFalse($mapping['machine_address']);
        $this->assertTrue($mapping['product_no']);
        $this->assertTrue($mapping['service_sku']);
        $this->assertFalse($mapping['description']);
        $this->assertTrue($mapping['serial_no']);
        $this->assertTrue($mapping['date_from']);
        $this->assertTrue($mapping['date_to']);
        $this->assertFalse($mapping['qty']);
        $this->assertTrue($mapping['price']);
        $this->assertFalse($mapping['searchable']);
        $this->assertTrue($mapping['service_level_description']);
    }

    /**
     * Test an ability to include original price column in mapping of the distributor quotes.
     */
    public function testCanViewIncludedOriginalPriceColumnInMappingOnWorldwideQuoteState(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $opportunity = Opportunity::factory()->create(['contract_type_id' => CT_CONTRACT]);

        $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT, 'opportunity_id' => $opportunity->getKey(),
        ]);

        $wwQuote->activeVersion->update(['quote_template_id' => $template->getKey()]);

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
                'opportunity_supplier_id' => $opportunitySupplier->getKey(),
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'buy_price' => mt_rand(500, 2000),
            ]
        );

        $templateFields = TemplateField::query()
            ->where('is_system', true)
            ->whereIn('name', $this->app['config']['quote-mapping.worldwide_quote.fields'])
            ->pluck('id', 'name');

        $wwDistributions->each(function (WorldwideDistribution $distribution) use (
            $templateFields,
            $opportunity,
            $vendor
        ) {
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

            $distribution->templateFields()->sync($templateFields);

            $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

            $distribution->opportunitySupplier()->associate($opportunitySupplier)->save();

            $snDiscount = factory(SND::class)->create();

            $distribution->snDiscount()->associate($snDiscount)->save();
        });

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.mapping',
                    'worldwide_distributions.mapping.original_price',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapping' => [
                            '*' => [
                                'template_field_id',
                                'importable_column_id',
                                'is_default_enabled',
                                'is_editable',
                                'sort',
                                'template_field_name',
                                'template_field_header',
                                'is_required',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('worldwide_distributions.*.mapping'));

        foreach ($response->json('worldwide_distributions.*.mapping') as $mappingOfDistributorQuote) {
            for ($i = 0; $i < count($mappingOfDistributorQuote); ++$i) {
                if ($mappingOfDistributorQuote[$i]['template_field_name'] === 'original_price') {
                    $this->assertArrayHasKey($i + 1, $mappingOfDistributorQuote);

                    $this->assertSame('price', $mappingOfDistributorQuote[$i + 1]['template_field_name']);
                }
            }
        }
    }

    /**
     * Test an ability to view prepared sales order data of worldwide contract quote.
     */
    public function testCanViewWorldwideContractQuoteSalesOrderData(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $country = Country::query()->first();
        $vendor = factory(Vendor::class)->create();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote * */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwQuote->activeVersion->update(['quote_template_id' => $template->getKey()]);

        factory(ContractTemplate::class)->create([
            'company_id' => $wwQuote->activeVersion->company_id,
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
        ]);

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
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'buy_price' => mt_rand(500, 2000),
            ]
        );

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($vendor) {
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

            $distribution->templateFields()->sync(TemplateField::query()
                ->where('is_system', true)
                ->whereIn('name', [
                    'product_no',
                    'description',
                    'serial_no',
                    'date_from',
                    'date_to',
                    'qty',
                    'price',
                    'searchable',
                    'service_level_description',
                ])
                ->pluck('id'));
        });

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/sales-order-data')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'worldwide_quote_number',
                'vat_number',
                'vat_type',
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
     * Test an ability to view worldwide quote preview data.
     *
     * @group worldwide-preview
     */
    public function testCanViewWorldwideQuotePreviewData(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create([
            'form_data' => [
                'first_page' => [
                    [
                        'id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                        'name' => 'Two Column',
                        'child' => [
                            [
                                'id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                'class' => 'col-lg-4',
                                'controls' => [
                                    [
                                        'id' => 'c0093711-ea9d-478d-b288-8433ef02af26',
                                        'src' => null,
                                        'name' => 'l-1',
                                        'show' => true,
                                        'type' => 'label',
                                        'class' => 'bold',
                                        'label' => 'Label',
                                        'value' => 'List Price:',
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => false,
                                        'is_required' => false,
                                        'attached_child_id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                        'attached_element_id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                                    ],
                                ],
                                'position' => 1,
                            ],
                            [
                                'id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                'class' => 'col-lg-8',
                                'controls' => [
                                    [
                                        'id' => 'list_price',
                                        'name' => 'list_price',
                                        'type' => 'tag',
                                        'class' => 's4-form-control text-nowrap',
                                        'label' => 'Total List Price',
                                        'value' => null,
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => true,
                                        'is_required' => false,
                                        'attached_child_id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                        'attached_element_id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                                    ],
                                ],
                                'position' => 2,
                            ],
                        ],
                        'class' => 'two-column field-dragger',
                        'order' => 3,
                        'controls' => [],
                        'is_field' => false,
                        'droppable' => false,
                        'decoration' => '1+3',
                    ],
                    [
                        'id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                        'name' => 'Two Column',
                        'child' => [
                            [
                                'id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                'class' => 'col-lg-4',
                                'controls' => [
                                    [
                                        'id' => 'c0093711-ea9d-478d-b288-8433ef02af26',
                                        'src' => null,
                                        'name' => 'l-1',
                                        'show' => true,
                                        'type' => 'label',
                                        'class' => 'bold',
                                        'label' => 'Label',
                                        'value' => 'Applicable Discounts:',
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => false,
                                        'is_required' => false,
                                        'attached_child_id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                        'attached_element_id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                                    ],
                                ],
                                'position' => 1,
                            ],
                            [
                                'id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                'class' => 'col-lg-8',
                                'controls' => [
                                    [
                                        'id' => 'applicable_discounts',
                                        'name' => 'applicable_discounts',
                                        'type' => 'tag',
                                        'class' => 's4-form-control text-nowrap',
                                        'label' => 'Total Discounts',
                                        'value' => null,
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => true,
                                        'is_required' => false,
                                        'attached_child_id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                        'attached_element_id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                                    ],
                                ],
                                'position' => 2,
                            ],
                        ],
                        'class' => 'two-column field-dragger',
                        'order' => 3,
                        'controls' => [],
                        'is_field' => false,
                        'droppable' => false,
                        'decoration' => '1+3',
                    ],
                ],
            ],
        ]);

        $template->vendors()->sync(Vendor::limit(2)->get());

        $opportunity = Opportunity::factory()->create();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(), 'contract_type_id' => CT_CONTRACT,
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'quote_currency_id' => Currency::query()->where('code', 'USD')->value('id'),
        ]);

        $country = Country::query()->first();
        $vendor = Vendor::query()->where('is_system', true)->first();

        $snDiscount = factory(SND::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $prePayDiscount = factory(PrePayDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);

        $templateFields = TemplateField::query()->where('is_system', true)
//            ->whereNotIn('name', ['pricing_document', 'system_handle', 'searchable'])
            ->pluck('id', 'name');
        $importableColumns = ImportableColumn::query()->where('is_system', true)->pluck('id', 'name');

        $mapping = [
            $templateFields['product_no'] => ['importable_column_id' => $importableColumns['product_no']],
            $templateFields['description'] => ['importable_column_id' => $importableColumns['description']],
            $templateFields['serial_no'] => ['importable_column_id' => $importableColumns['serial_no']],
            $templateFields['date_from'] => ['importable_column_id' => $importableColumns['date_from']],
            $templateFields['date_to'] => ['importable_column_id' => $importableColumns['date_to']],
            $templateFields['qty'] => ['importable_column_id' => $importableColumns['qty']],
            $templateFields['price'] => ['importable_column_id' => $importableColumns['price']],
            $templateFields['searchable'] => ['importable_column_id' => $importableColumns['searchable']],
            $templateFields['service_level_description'] => [
                'importable_column_id' => factory(ImportableColumn::class)
                    ->create()
                    ->getKey(),
            ],
        ];

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create(
            [
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'tax_value' => 10,
                'custom_discount' => 2,
                'buy_price' => 500,
                'distribution_currency_id' => Currency::where('code', 'GBP')->value('id'),
//                'multi_year_discount_id' => $multiYearDiscount->getKey(),
            ]
        );

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($mapping, $vendor, $opportunity) {
            $distribution->vendors()->sync($vendor);

            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $mappedRows = factory(MappedRow::class, 2)->create([
                'quote_file_id' => $distributorFile->getKey(), 'is_selected' => true, 'price' => 250,
            ]);

            $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $distribution->getKey()]);
            $rowsGroup->rows()->sync($mappedRows->modelKeys());

            $distribution->templateFields()->sync($mapping);

            $distribution->addresses()->sync(factory(Address::class)->create(['address_type' => 'Machine']));

            $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($opportunitySupplier);
            $distribution->save();
        });

        $wwDistributions[0]->use_groups = true;
        $wwDistributions[0]->save();

        $group = factory(DistributionRowsGroup::class)->create([
            'worldwide_distribution_id' => $wwDistributions[0]->getKey(),
            'is_selected' => true,
        ]);

        $group->rows()->sync($wwDistributions[0]->mappedRows()->pluck('mapped_rows.id'));

        $paymentScheduleFile = factory(QuoteFile::class)->create([
            'file_type' => 'Payment Schedule',
        ]);

        factory(ScheduleData::class)->create([
            'quote_file_id' => $paymentScheduleFile->getKey(),
        ]);

        $wwDistributions[0]->schedule_file_id = $paymentScheduleFile->getKey();
        $wwDistributions[0]->save();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/preview')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'template_data' => [
                    'first_page_schema',
                    'assets_page_schema',
                    'payment_schedule_page_schema',
                    'last_page_schema',
                    'template_assets',
                    'headers' => [
                        'total',
                        'total_price',
                        'sub_total',
                        'total_including_tax',
                        'grand_total',
                    ],
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
                ],

                'distributions' => [
                    '*' => [
                        'vendors', 'country', 'supplier',
                        'asset_fields' => [
                            '*' => ['field_name', 'field_header'],
                        ],
//                        'assets_data' => [
//                            '*' => [
//                                'buy_currency_code'
//                            ]
//                        ],
                    ],
                ],
            ]);

        $assetDataFields = [
            'buy_currency_code',
            'vendor_short_code',
            'product_no',
            'service_sku',
            'description',
            'serial_no',
            'is_serial_number_generated',
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
        ];

        $assetFields = [
            'product_no',
            'description',
            'serial_no',
            'date_from',
            'date_to',
            'qty',
            'price',
            'service_level_description',
            'machine_address_string',
        ];

        foreach ($response->json('distributions') as $distributorQuote) {
            $assets = collect($distributorQuote['assets_data']);

            if ($distributorQuote['assets_are_grouped']) {
                $assets = $assets->pluck('assets')->collapse()->all();
            }

            foreach ($assetFields as $field) {
                $this->assertContainsEquals($field, Arr::pluck($distributorQuote['asset_fields'], 'field_name'));
            }

            foreach ($assets as $asset) {
                foreach ($assetDataFields as $field) {
                    $this->assertArrayHasKey($field, $asset);
                }
            }
        }

        $this->assertSame('$ 1,111.11', $response->json('quote_summary.list_price'));
        $this->assertSame('$ 1,086.96', $response->json('quote_summary.final_price'));
        $this->assertSame('$ 24.15', $response->json('quote_summary.applicable_discounts'));
        $this->assertSame('$ 1,106.96', $response->json('quote_summary.total_value_including_tax'));
    }

    /**
     * Test an ability to view grouped assets with the same serial no in preview data.
     */
    public function testCanViewGroupedAssetsWithSameSerialInPreviewData(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create([
            'form_data' => [
                'first_page' => [
                    [
                        'id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                        'name' => 'Two Column',
                        'child' => [
                            [
                                'id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                'class' => 'col-lg-4',
                                'controls' => [
                                    [
                                        'id' => 'c0093711-ea9d-478d-b288-8433ef02af26',
                                        'src' => null,
                                        'name' => 'l-1',
                                        'show' => true,
                                        'type' => 'label',
                                        'class' => 'bold',
                                        'label' => 'Label',
                                        'value' => 'List Price:',
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => false,
                                        'is_required' => false,
                                        'attached_child_id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                        'attached_element_id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                                    ],
                                ],
                                'position' => 1,
                            ],
                            [
                                'id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                'class' => 'col-lg-8',
                                'controls' => [
                                    [
                                        'id' => 'list_price',
                                        'name' => 'list_price',
                                        'type' => 'tag',
                                        'class' => 's4-form-control text-nowrap',
                                        'label' => 'Total List Price',
                                        'value' => null,
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => true,
                                        'is_required' => false,
                                        'attached_child_id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                        'attached_element_id' => 'bb9ea3cc-95eb-4be1-b78d-7565b68c78cf',
                                    ],
                                ],
                                'position' => 2,
                            ],
                        ],
                        'class' => 'two-column field-dragger',
                        'order' => 3,
                        'controls' => [],
                        'is_field' => false,
                        'droppable' => false,
                        'decoration' => '1+3',
                    ],
                    [
                        'id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                        'name' => 'Two Column',
                        'child' => [
                            [
                                'id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                'class' => 'col-lg-4',
                                'controls' => [
                                    [
                                        'id' => 'c0093711-ea9d-478d-b288-8433ef02af26',
                                        'src' => null,
                                        'name' => 'l-1',
                                        'show' => true,
                                        'type' => 'label',
                                        'class' => 'bold',
                                        'label' => 'Label',
                                        'value' => 'Applicable Discounts:',
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => false,
                                        'is_required' => false,
                                        'attached_child_id' => '2521a5c6-6eda-4420-876d-b821841f892e',
                                        'attached_element_id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                                    ],
                                ],
                                'position' => 1,
                            ],
                            [
                                'id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                'class' => 'col-lg-8',
                                'controls' => [
                                    [
                                        'id' => 'applicable_discounts',
                                        'name' => 'applicable_discounts',
                                        'type' => 'tag',
                                        'class' => 's4-form-control text-nowrap',
                                        'label' => 'Total Discounts',
                                        'value' => null,
                                        'is_field' => true,
                                        'is_image' => false,
                                        'droppable' => false,
                                        'is_system' => true,
                                        'is_required' => false,
                                        'attached_child_id' => '49e0ebf9-8c4d-422b-8641-7d5622e6f087',
                                        'attached_element_id' => 'a7623070-c87b-428f-a894-87ccfcb909c2',
                                    ],
                                ],
                                'position' => 2,
                            ],
                        ],
                        'class' => 'two-column field-dragger',
                        'order' => 3,
                        'controls' => [],
                        'is_field' => false,
                        'droppable' => false,
                        'decoration' => '1+3',
                    ],
                ],
            ],
        ]);

        $template->vendors()->sync(Vendor::limit(2)->get());

        $opportunity = Opportunity::factory()->create();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(), 'contract_type_id' => CT_CONTRACT,
        ]);

        $wwQuote->activeVersion->update([
            'quote_template_id' => $template->getKey(),
            'quote_currency_id' => Currency::query()->where('code', 'USD')->value('id'),
        ]);

        $country = Country::query()->first();
        $vendor = Vendor::query()->where('is_system', true)->first();

        $snDiscount = factory(SND::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $prePayDiscount = factory(PrePayDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
        ]);

        $templateFields = TemplateField::query()->where('is_system', true)
//            ->whereNotIn('name', ['pricing_document', 'system_handle', 'searchable'])
            ->pluck('id', 'name');
        $importableColumns = ImportableColumn::query()->where('is_system', true)->pluck('id', 'name');

        $mapping = [
            $templateFields['product_no'] => ['importable_column_id' => $importableColumns['product_no']],
            $templateFields['description'] => ['importable_column_id' => $importableColumns['description']],
            $templateFields['serial_no'] => ['importable_column_id' => $importableColumns['serial_no']],
            $templateFields['date_from'] => ['importable_column_id' => $importableColumns['date_from']],
            $templateFields['date_to'] => ['importable_column_id' => $importableColumns['date_to']],
            $templateFields['qty'] => ['importable_column_id' => $importableColumns['qty']],
            $templateFields['price'] => ['importable_column_id' => $importableColumns['price']],
            $templateFields['searchable'] => ['importable_column_id' => $importableColumns['searchable']],
            $templateFields['service_level_description'] => [
                'importable_column_id' => factory(ImportableColumn::class)
                    ->create()
                    ->getKey(),
            ],
        ];

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create(
            [
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'country_id' => $country->getKey(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'asc',
                'margin_value' => 10,
                'tax_value' => 10,
                'custom_discount' => 2,
                'buy_price' => 500,
                'distribution_currency_id' => Currency::where('code', 'GBP')->value('id'),
//                'multi_year_discount_id' => $multiYearDiscount->getKey(),
            ]
        );

        $repeatedSerialNo = Str::random(40);

        $wwDistributions->each(function (WorldwideDistribution $distribution) use (
            $mapping,
            $vendor,
            $opportunity,
            $repeatedSerialNo
        ) {
            $distribution->vendors()->sync($vendor);

            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $mappedRows = factory(MappedRow::class, 4)->create([
                'quote_file_id' => $distributorFile->getKey(), 'is_selected' => true, 'price' => 250,
            ]);

            /** @var \App\Domain\DocumentMapping\Models\MappedRow[] $mappedRowsWithSameSerial */
            $mappedRowsWithSameSerial = [
                $mappedRows[0],
                $mappedRows[count($mappedRows) - 1],
            ];

            $mappedRowsWithSameSerial[0]->serial_no = $repeatedSerialNo;
            $mappedRowsWithSameSerial[1]->serial_no = $repeatedSerialNo;
            $mappedRowsWithSameSerial[0]->save();
            $mappedRowsWithSameSerial[1]->save();

            $distribution->templateFields()->sync($mapping);

            $distribution->addresses()->sync(factory(Address::class)->create(['address_type' => 'Machine']));

            $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($opportunitySupplier);
            $distribution->save();
        });

        $paymentScheduleFile = factory(QuoteFile::class)->create([
            'file_type' => 'Payment Schedule',
        ]);

        factory(ScheduleData::class)->create([
            'quote_file_id' => $paymentScheduleFile->getKey(),
        ]);

        $wwDistributions[0]->schedule_file_id = $paymentScheduleFile->getKey();
        $wwDistributions[0]->save();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/preview')
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
                ],

                'distributions' => [
                    '*' => [
                        'vendors', 'country', 'supplier',
                        'asset_fields' => [
                            '*' => ['field_name', 'field_header'],
                        ],
                        'assets_data' => [
                            '*' => [
                                'serial_no',
                            ],
                        ],
                    ],
                ],
            ]);

        $assetDataFields = [
            'buy_currency_code',
            'vendor_short_code',
            'product_no',
            'service_sku',
            'description',
            'serial_no',
            'is_serial_number_generated',
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
        ];

        $assetFields = [
            'product_no',
            'description',
            'serial_no',
            'date_from',
            'date_to',
            'qty',
            'price',
            'service_level_description',
            'machine_address_string',
        ];

        $keyOfFirstAssetWithSameSerial = collect($response->json('distributions.0.assets_data.*.serial_no'))->search($repeatedSerialNo);

        $this->assertNotFalse($keyOfFirstAssetWithSameSerial);

        $this->assertSame($repeatedSerialNo,
            $response->json('distributions.0.assets_data.'.($keyOfFirstAssetWithSameSerial + 1).'.serial_no'));
    }

    /**
     * Test an ability to submit worldwide quote.
     */
    public function testCanSubmitWorldwideQuote(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('assets')->delete();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
        $wwQuote = WorldwideQuote::factory()
            ->for(
                Opportunity::factory()
                    ->for(
                        Company::factory()->hasAttached(
                            Address::factory(['address_type' => AddressType::INVOICE]),
                            pivot: ['is_default' => true]
                        ),
                        relationship: 'primaryAccount'
                    )
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
            ]);

        $wwQuote->activeVersion->quoteTemplate()->associate(QuoteTemplate::query()->first());
        $wwQuote->activeVersion->user()->associate($this->app['auth']->user());
        $wwQuote->activeVersion->save();

        $noteForDraftedQuote = Note::factory()
            ->hasAttached($wwQuote, relationship: 'worldwideQuotesHaveNote')
            ->hasAttached($wwQuote->activeVersion, relationship: 'worldwideQuoteVersionsHaveNote')
            ->hasAttached($wwQuote->opportunity, relationship: 'opportunitiesHaveNote')
            ->create([
                'note' => 'Note for drafted quote',
            ]);

        $notesResponse = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'created_at',
                        'updated_at',
                        'user',
                    ],
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ]);

        $this->assertSame(1, $notesResponse->json('total'));

        $supplier = factory(OpportunitySupplier::class)->create([
            'opportunity_id' => $wwQuote->opportunity->getKey(),
        ]);

        $distributorFile = factory(QuoteFile::class)->create();

        /** @var \App\Domain\Worldwide\Models\WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)
            ->create([
                'country_id' => Country::query()->where('iso_3166_2', 'GB')->sole()->getKey(),
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
                'distributor_file_id' => $distributorFile->getKey(),
                'opportunity_supplier_id' => $supplier->getKey(),
            ]);

        $distributorQuote->vendors()->sync(Vendor::query()->where('short_code', 'HPE')->sole());

        /** @var \App\Domain\DocumentMapping\Models\MappedRow $mappedRow */
        $mappedRow = factory(MappedRow::class)->create([
            'quote_file_id' => $distributorFile->getKey(),
        ]);

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/submit', [
            'quote_closing_date' => $this->faker->date(),
            'additional_notes' => 'Note for submitted quote',
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));

        $response = $this->get('api/assets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'rfq_number', 'customer_name', 'vendor_short_code', 'product_number', 'serial_number',
                        'product_description',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(1, $response->json('data'));

        $this->assertSame('HPE', $response->json('data.0.vendor_short_code'));
        $this->assertSame($wwQuote->quote_number, $response->json('data.0.rfq_number'));
        $this->assertSame($wwQuote->opportunity->primaryAccount->name, $response->json('data.0.customer_name'));

        $this->assertSame($mappedRow->product_no, $response->json('data.0.product_number'));
        $this->assertSame($mappedRow->serial_no, $response->json('data.0.serial_number'));
        $this->assertSame($mappedRow->description, $response->json('data.0.product_description'));

        // Assert a separated note created on quote submission.
        $notesResponse = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'created_at',
                        'updated_at',
                        'user',
                    ],
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ]);

        $this->assertSame(2, $notesResponse->json('total'));

        $notesText = $notesResponse->json('data.*.text');

        $this->assertContainsEquals('Note for drafted quote', $notesText);
        $this->assertContainsEquals('Note for submitted quote', $notesText);
    }

    /**
     * Test an ability to draft worldwide quote.
     */
    public function testCanDraftWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create(['submitted_at' => null]);

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/draft', [
            'quote_closing_date' => $this->faker->date,
            'additional_notes' => $this->faker->text(10000),
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to unravel submitted worldwide quote.
     */
    public function testCanUnravelWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertNotEmpty($response->json('submitted_at'));

        $this->patchJson('api/ww-quotes/'.$wwQuote->getKey().'/unravel')->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ]);

        $this->assertEmpty($response->json('submitted_at'));
    }

    /**
     * Test an ability to delete a newly created worldwide quote.
     */
    public function testCanDeleteWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $this->deleteJson('api/ww-quotes/'.$wwQuote->getKey())
//            ->dump()
            ->assertNoContent();
    }

    /**
     * Test an ability to export a submitted worldwide quote.
     */
    public function testCanExportWorldwideQuote(): void
    {
//        $this->markTestSkipped();

        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $templateSchema = <<<TEMPLATE
{"last_page": [{"id": "ae296560-047e-4cd1-8a30-d4481159eb80", "name": "Single Column", "child": [{"id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "class": "col-lg-12", "controls": [{"id": "04bb6182-2768-486b-9961-1b5bdebaefbf", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Why choose Support Warehouse to deliver your HPE Services Contract?", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "attached_element_id": "ae296560-047e-4cd1-8a30-d4481159eb80"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "7487ada6-1182-4737-9a62-34c893e7fbd1", "name": "Single Column", "child": [{"id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "class": "col-lg-12", "controls": [{"id": "cd2c69d9-4956-494a-9a82-a20d4a7e3ed2", "src": null, "name": "richtext-1", "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><strong>Account Management</strong> – Your account manager will help you to manage your services contract, and will arrange quarterly support reviews for you to ensure that the service levels within the services contract remain appropriate for the applications running on the hardware. If your IT environment changes, with the addition or decommissioning of hardware, we can update your services contract at any time.</p><p><strong>Renewal Service</strong> – Your account manager will remind you when your services contract is due to expire, normally 45 to 90 days in advance. This gives us enough time to review your current IT support, take into account any changes that have taken place in your IT environment, and create an up-to-date tailored quotation.</p><p><strong>Flexible Payment Options</strong> – You can choose invoicing terms to suit your budget and business preferences, as we offer upfront, annual or quarterly payment options (subject to terms and conditions). Please note that the payment and invoicing terms for this quote are stated in the quotation summary (final price is subject to change if invoicing terms are changed).</p><p><strong>Assistance with HPE tools</strong> – We are experienced in using the proprietary tools and resources available to make managing your IT support easier. We can help you to link your support with the Support Centre portal and introduce you to contacts that can assist with installing IRS.</p><p><strong>Support for the whole lifecycle</strong> – Support Warehouse can provide support for your IT environment from initial product purchase through to decommissioning and technology refresh.</p><p>Consolidate your IT Support – Your account manager will help you to consolidate your various IT support agreements and certificates under one HPE services contract. This can include HPE hardware and some multi-vendor hardware.</p><p><strong>Flexibility</strong> – Once a services contract is in place with HPE, it is possible for you to add new hardware to the contract (with 30 days’ notice) or remove hardware from the contract (with 90 days’ notice). Any difference in cost will be invoiced or credited accordingly. Services Contracts can also be cancelled entirely, subject to minimum periods of cover and notice periods.</p><p><br></p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "attached_element_id": "7487ada6-1182-4737-9a62-34c893e7fbd1"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "328a2ad6-9090-478e-b719-b23489239dd1", "css": null, "src": null, "name": "h-1", "show": false, "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "fb35cfb7-fdde-4022-91d0-3524b3df4f32"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "9d794895-65cf-4656-be58-1d9a2715bb04", "name": "Single Column", "child": [{"id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "class": "col-lg-12 border-right", "controls": [{"id": "cbb82c75-c7a1-4cf9-be44-a1d60c696665", "css": null, "src": null, "name": "richtext-1", "show": false, "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><span style=\"color: rgb(187, 187, 187);\">Company Reg.: 4056599, Company VAT: 758 5011 25&nbsp;&nbsp;</span></p><p><span style=\"color: rgb(187, 187, 187);\">UK registered company. Registered office: Support Warehouse Ltd, International Development Centre, Valley Drive, Ilkley, West Yorkshire, LS29 8PB</span></p><p><span style=\"color: rgb(187, 187, 187);\">Tel: 0800 072 0950</span></p><p><span style=\"color: rgb(187, 187, 187);\">Email: gb@supportwarehouse.com</span></p><p><span style=\"color: rgb(187, 187, 187);\">Visit: www.supportwarehouse.com/hpe-contract-services</span></p><p><span style=\"color: rgb(187, 187, 187);\">Terms &amp; Conditions: http://www.supportwarehouse.com/support-warehouse-limited-terms-conditions-business/</span></p><p><br></p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "543128e3-6fd9-468d-a5bc-8fff75a32898", "attached_element_id": "9d794895-65cf-4656-be58-1d9a2715bb04"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "data_pages": [{"id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47", "name": "Single Column", "child": [{"id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "class": "col-lg-12", "controls": [{"id": "820d7aea-804a-423b-8d63-3696f70ba365", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Quotation Detail", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "attached_element_id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "09972220-37f0-4e7e-b9cf-796772aa4472", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Pricing Document", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "pricing_document", "name": "pricing_document", "type": "tag", "class": "s4-form-control text-size-17", "label": "Pricing Document", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Service Agreement ID", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "service_agreement_id", "name": "service_agreement_id", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Service Agreement Id", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "09972220-37f0-4e7e-b9cf-796772aa4472"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "a9b07900-98d4-4803-b985-167c8b202b32", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "type": "label", "class": "bold", "label": "Label", "value": "System Handle", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "a9b07900-98d4-4803-b985-167c8b202b32"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "system_handle", "name": "system_handle", "type": "tag", "class": "s4-form-control text-size-17", "label": "System Handle", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "a9b07900-98d4-4803-b985-167c8b202b32"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Equipment Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "equipment_address", "name": "equipment_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Equipment Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Software Update Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_address", "name": "software_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Hardware Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_contact", "name": "hardware_contact", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Software Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_contact", "name": "software_contact", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_phone", "name": "hardware_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_phone", "name": "software_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "736ac1e0-52c8-4f80-bc81-7bae72e10102", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Coverage Period", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "coverage_period_from", "name": "coverage_period_from", "type": "tag", "class": "s4-form-control text-size-17", "label": "Coverage Period From", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "type": "label", "class": "text-size-17", "label": "Label", "value": "to", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "coverage_period_to", "name": "coverage_period_to", "type": "tag", "class": "s4-form-control text-size-17", "label": "Coverage Period To", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "be234931-7998-40c4-9b23-16b113c57e21", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12 mt-2", "controls": [{"id": "91df5263-00d6-40c5-b327-00765c4fc8b2", "src": null, "name": "l-1", "type": "label", "class": "bold", "label": "Label", "value": "Service Level (s):", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}, {"id": "service_levels", "name": "service_levels", "show": true, "type": "tag", "class": "s4-form-control", "label": "Service Level", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "be234931-7998-40c4-9b23-16b113c57e21"}], "position": 1}], "class": "single-column field-dragger", "order": 4, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "first_page": [{"id": "1a4397e2-4995-454e-9af8-7af7b0af1638", "name": "Single Column", "child": [{"id": "9e8f266b-5609-4cdb-b6ce-187844a40081", "class": "col-lg-12 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "text-right", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8f266b-5609-4cdb-b6ce-187844a40081", "attached_element_id": "1a4397e2-4995-454e-9af8-7af7b0af1638"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "company_name", "css": null, "name": "company_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "205bb944-4333-4add-baa7-5ae17bf238a9", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Company", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "customer_name", "css": null, "name": "customer_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "44ee34b7-4ab7-44da-9134-9994259f85d0", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Contact Name", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "contact_name", "css": null, "name": "contact_name", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79", "name": "Two Column", "child": [{"id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "class": "col-lg-3", "controls": [{"id": "04ae8306-7a58-41c5-8d95-8581bbc6bebb", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "blue", "label": "Label", "value": "Email", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "attached_element_id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79"}], "position": 1}, {"id": "4fe14638-75d9-4353-85a0-ec1e96793175", "class": "col-lg-9 border-right", "controls": [{"id": "contact_email", "css": null, "name": "contact_email", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Email", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4fe14638-75d9-4353-85a0-ec1e96793175", "attached_element_id": "5af26a36-cc67-4a6f-aa9a-9a6e2b1bdb79"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98", "name": "Two Column", "child": [{"id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "class": "col-lg-3", "controls": [{"id": "04ae8306-7a58-41c5-8d95-8581bbc6bebb", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "blue", "label": "Label", "value": "Phone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "bab6e06c-fcb0-4253-b8cf-9680b991c322", "attached_element_id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98"}], "position": 1}, {"id": "4fe14638-75d9-4353-85a0-ec1e96793175", "class": "col-lg-9 border-right", "controls": [{"id": "contact_phone", "css": null, "name": "contact_phone", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contact Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4fe14638-75d9-4353-85a0-ec1e96793175", "attached_element_id": "676c7398-89a5-4a0c-8b1c-54d0bc7d5a98"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "8c74b0d3-f439-4383-a442-e4386fb957d9", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12", "controls": [{"id": "ae1e9dc9-9458-4172-a2b7-6086a29ce1b0", "css": null, "src": null, "name": "h-1", "show": true, "type": "hr", "class": "blue", "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "8c74b0d3-f439-4383-a442-e4386fb957d9"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "a8661be5-1b81-43ad-a614-2fefc2db80eb", "name": "Single Column", "child": [{"id": "88647d86-d061-43d5-9078-1e232b5ea47e", "class": "col-lg-12", "controls": [{"id": "0b2402a3-38de-47f8-a0f6-83b7c3cc3764", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Quotation Summary", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "88647d86-d061-43d5-9078-1e232b5ea47e", "attached_element_id": "a8661be5-1b81-43ad-a614-2fefc2db80eb"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "b54449f5-9995-4cc9-9377-a1f1d3a3a8d1", "name": "Single Column", "child": [{"id": "6ec1142b-0fa2-416c-a4b9-d6c2ad3e9da3", "class": "col-lg-12 border-right", "controls": [{"id": "04ae8306-7a58-41c5-8d95-8581bbc6bebb", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "blue", "label": "Label", "value": "Quoted Service Level", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "6ec1142b-0fa2-416c-a4b9-d6c2ad3e9da3", "attached_element_id": "b54449f5-9995-4cc9-9377-a1f1d3a3a8d1"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "001b2b59-a062-41bf-9672-6e468dbde67e", "name": "Single Column", "child": [{"id": "6ec1142b-0fa2-416c-a4b9-d6c2ad3e9da3", "class": "col-lg-12 border-right", "controls": [{"id": "service_levels", "css": null, "name": "service_levels", "show": false, "type": "tag", "class": "s4-form-control", "label": "Service Level (s)", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "6ec1142b-0fa2-416c-a4b9-d6c2ad3e9da3", "attached_element_id": "001b2b59-a062-41bf-9672-6e468dbde67e"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "e1b21561-9910-4375-b324-bbee46c8bbf4", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "quotation_number", "name": "quotation_number", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "RFQ Number", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "344e5c52-2caf-4376-a4b3-915ca7a12965", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "css": "font-size: 12px", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Valid Until:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "valid_until", "name": "valid_until", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "Quotation Closing Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support Start Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support End Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "List Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "list_price", "name": "list_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total List Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "a7623070-c87b-428f-a894-87ccfcb909c2", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Applicable Discounts:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "applicable_discounts", "name": "applicable_discounts", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total Discounts", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Final Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "final_price", "name": "final_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Final Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}, {"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "(All prices are excluding applicable taxes)", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Payment Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "6467a459-8603-44de-8f51-b4ce915ce8db", "src": null, "name": "l-1", "show": true, "type": "label", "class": "text-nowrap", "label": "Label", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "c174ce4d-8e57-430a-961f-b1c1933d4127", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Invoicing Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "c174ce4d-8e57-430a-961f-b1c1933d4127"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "invoicing_terms", "name": "invoicing_terms", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Invoicing Terms", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "c174ce4d-8e57-430a-961f-b1c1933d4127"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3"}, {"id": "fa3a8604-9e8f-45a1-bb3e-246b8a099a92", "name": "Single Column", "child": [{"id": "37b9807a-e66d-4a73-94e3-62a99e6b48fb", "class": "col-lg-12 border-right", "controls": [{"id": "quote_data_aggregation", "css": null, "name": "quote_data_aggregation", "show": false, "type": "tag", "class": "s4-form-control", "label": "Quote Data Aggregation", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "37b9807a-e66d-4a73-94e3-62a99e6b48fb", "attached_element_id": "fa3a8604-9e8f-45a1-bb3e-246b8a099a92"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "2027b6f8-5391-406a-846a-a9d6b3c2dff9", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Purchase Order Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "2027b6f8-5391-406a-846a-a9d6b3c2dff9"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "db604244-68f5-49bd-9a6b-6a7a93f83c2d", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "VAT Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "db604244-68f5-49bd-9a6b-6a7a93f83c2d"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "899208a2-766b-43b5-8adc-8d384e5999d8", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "899208a2-766b-43b5-8adc-8d384e5999d8"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "8322df1d-bf7c-4c0f-984b-9f925035eabe", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Signature:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "8322df1d-bf7c-4c0f-984b-9f925035eabe"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "9c378e04-49eb-472d-bead-5a02d628303b", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Contact Us", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "9c378e04-49eb-472d-bead-5a02d628303b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "eb4410df-3cb1-4e68-866a-f294658768b7", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Tel:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "0800 072 0950", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "5591650b-9c22-4f55-a286-916b64aa5588", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Email:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "gb@supportwarehouse.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}, {"id": "fef1dba7-386f-4959-bd7d-121de4a0cd54", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Visit:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "www.supportwarehouse.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1"}], "payment_page": [{"id": "7403b962-3dcd-43a9-b378-f52c616bbe9d", "name": "Three Column", "child": [{"id": "62d78c91-3073-4fa4-95fb-d19cb52610b3", "class": "col-lg-6", "controls": [], "position": 1}, {"id": "271be802-e645-4b7f-863c-efb911a1089a", "class": "col-lg-3 border-right", "controls": [{"id": "company_logo_x3", "src": "http://68.183.35.153/easyQuote-API/public/storage/images/companies/ypx1Wm6EUpFkEq3bqhKqTCBDpSFUVlsmhzLA1yob@x3.png", "name": "company_logo_x3", "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "271be802-e645-4b7f-863c-efb911a1089a", "attached_element_id": "7403b962-3dcd-43a9-b378-f52c616bbe9d"}], "position": 2}, {"id": "af8777bb-6c28-4d30-86b3-e3ab425d8b8b", "class": "col-lg-3", "controls": [{"id": "vendor_logo_x3", "src": "http://68.183.35.153/easyQuote-API/public/storage/images/vendors/2xzg79lx3TnfLzkAJcWlbkrJygTL1IQGAUYqNnQY@x3.png", "name": "vendor_logo_x3", "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "af8777bb-6c28-4d30-86b3-e3ab425d8b8b", "attached_element_id": "7403b962-3dcd-43a9-b378-f52c616bbe9d"}], "position": 3}], "class": "three-column field-dragger", "order": 4, "controls": [], "is_field": false, "droppable": false, "decoration": "1+2"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "vendor_name", "css": null, "name": "vendor_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Vendor Full Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "eb1e8eb2-2616-455c-aa10-52578736687e"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "c83dff46-b6ef-4553-bc69-775d3694a487", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "54682e57-49d0-47c0-bb51-44233fa41438", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "13612e0d-7a90-420f-88cc-73ea17046865", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "087c4ed7-f16b-4250-b025-69ec72801d2f", "src": null, "name": "h-1", "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "13612e0d-7a90-420f-88cc-73ea17046865"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "baadd19a-a0b7-491c-930b-e1325653c3ce", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "Payment Schedule From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "To", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}]}
TEMPLATE;

        $template->form_data = json_decode($templateSchema);
        $template->save();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        $wwQuote->activeVersion->update(['quote_template_id' => $template->getKey()]);

        /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
        $opportunity = $wwQuote->opportunity;

        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Hardware'])
        );
        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Software'])
        );

        $country = Country::query()->first();
        $vendor = Vendor::query()->where('is_system', true)->first();

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
                'worldwide_quote_type' => $wwQuote->getMorphClass(),
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

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity, $mapping, $vendor) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

            $distribution->opportunitySupplier()->associate($supplier)->save();

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

        $expectedFileName = $wwQuote->quote_number.'.pdf';

        $response = $this->get('api/ww-quotes/'.$wwQuote->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");

//        $fakeStorage = Storage::persistentFake();
//
//        $fakeStorage->put($expectedFileName, $response->getContent());
    }

    /**
     * Test an ability to export a submitted worldwide quote,
     * when contract duration is checked in opportunity.
     */
    public function testCanExportContractWorldwideQuoteWhenContractDurationIsChecked(): void
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $templateSchema = <<<TEMPLATE
{"last_page": [{"id": "ae296560-047e-4cd1-8a30-d4481159eb80", "name": "Single Column", "child": [{"id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "class": "col-lg-12", "controls": [{"id": "04bb6182-2768-486b-9961-1b5bdebaefbf", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Why choose Europlus Direct to deliver your Vendor Support Services?", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7e7313c6-26a7-4c66-a63a-42a44297c67f", "attached_element_id": "ae296560-047e-4cd1-8a30-d4481159eb80"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "7487ada6-1182-4737-9a62-34c893e7fbd1", "name": "Single Column", "child": [{"id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "class": "col-lg-12", "controls": [{"id": "cd2c69d9-4956-494a-9a82-a20d4a7e3ed2", "src": null, "name": "richtext-1", "type": "richtext", "class": null, "label": "Rich Text", "value": "<p><strong>Account Management</strong> – Your account manager will help you to manage your services contract, and will arrange quarterly support reviews for you to ensure that the service levels within the services contract remain appropriate for the applications running on the hardware. If your IT environment changes, with the addition or decommissioning of hardware, we can update your services contract at any time.</p><p><strong>Renewal Service</strong> – Your account manager will remind you when your services contract is due to expire, normally 45 to 90 days in advance. This gives us enough time to review your current IT support, take into account any changes that have taken place in your IT environment, and create an up-to-date tailored quotation.</p><p><strong>Assistance with Vendor tools</strong> – We are experienced in using the proprietary tools and resources available to make managing your IT support easier. We can help you to link your support with the Support Centre portal and introduce you to contacts that can assist with installing IRS.</p><p><strong>Support for the whole lifecycle</strong> – Europlus Direct can provide support for your IT environment from initial product purchase through to decommissioning and technology refresh.</p><p>Consolidate your IT Support – Your account manager will help you to consolidate your various IT support agreements and certificates under one Vendor services contract. This can include Vendor hardware and some multi-vendor hardware.</p><p><strong>Flexibility</strong> – Once a services contract is in place with Vendor, it is possible for you to add new hardware to the contract (with 30 days’ notice) or remove hardware from the contract (with 90 days’ notice). Any difference in cost will be invoiced or credited accordingly. Services Contracts can also be cancelled entirely, subject to minimum periods of cover and notice periods.</p>", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "20b8033e-434b-47ac-96c0-dfb361dcc6d3", "attached_element_id": "7487ada6-1182-4737-9a62-34c893e7fbd1"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}], "data_pages": [{"id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47", "name": "Single Column", "child": [{"id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "class": "col-lg-12", "controls": [{"id": "820d7aea-804a-423b-8d63-3696f70ba365", "src": null, "name": "h-1", "show": true, "type": "h", "class": null, "label": "Heading", "value": "Quotation Detail", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "f7f3b4e6-6a00-4748-9cfa-66c479b7e3b7", "attached_element_id": "a73915b6-29f2-4688-ae3d-ee8ef9054f47"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Equipment Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "equipment_address", "name": "equipment_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Equipment Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Software Update Address", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_address", "name": "software_address", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Address", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "5a412e13-6f7d-4716-8cbb-20aec130e2f9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Hardware Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_contact", "name": "hardware_contact", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Software Contact", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_contact", "name": "software_contact", "show": true, "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Contact", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "aea202ab-8bc6-4663-9c08-a73ae6ce91d1"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "hardware_phone", "name": "hardware_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Hardware Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Telephone", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [{"id": "software_phone", "name": "software_phone", "type": "tag", "class": "s4-form-control text-size-17", "label": "Software Phone", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "attached_element_id": "af9545d4-2f67-496c-99bc-35c08ff9c5c9"}], "position": 4}], "class": "four-column field-dragger", "order": 5, "controls": [], "is_field": false, "droppable": false, "decoration": "4"}, {"id": "736ac1e0-52c8-4f80-bc81-7bae72e10102", "name": "Four Column", "child": [{"id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "class": "col-lg-3", "controls": [{"id": "6b797fa6-c859-4ff1-9efc-8b74bf575930", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold text-nowrap", "label": "Label", "value": "Coverage Period", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "67b16d52-482b-4c90-8f68-3bfb7dace282", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 1}, {"id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "class": "col-lg-3", "controls": [{"id": "coverage_period", "css": null, "name": "coverage_period", "show": false, "type": "tag", "class": "s4-form-control", "label": "Coverage Period", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "0d86193d-1afb-4874-b3a6-03477e38c6ce", "attached_element_id": "736ac1e0-52c8-4f80-bc81-7bae72e10102"}], "position": 2}, {"id": "9daf1130-ab47-4b35-b256-af25e5bd8d11", "class": "col-lg-3", "controls": [], "position": 3}, {"id": "9e8a0b77-2bc4-4fde-9f18-5250907a425d", "class": "col-lg-3", "controls": [], "position": 4}], "class": "four-column field-dragger", "order": 5, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "4", "visibility": true}], "first_page": [{"id": "6cceda6e-2654-42c0-8943-ac8ac9d5b489", "hide": false, "name": "Two Column", "show": false, "child": [{"id": "2a1b5356-1f81-4746-9b06-b91d2149fa87", "class": "col-lg-3", "controls": [{"id": "company_logo_x3", "css": null, "src": "http://68.183.35.153/easyQuote-API/public/storage/images/companies/b86170d9-ceca-4f58-8a04-99ed2b0acb87@x3.png", "name": "company_logo_x3", "show": false, "type": "img", "class": "s4-form-control", "label": "Logo 240X120", "value": null, "is_field": true, "is_image": true, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "2a1b5356-1f81-4746-9b06-b91d2149fa87", "attached_element_id": "6cceda6e-2654-42c0-8943-ac8ac9d5b489"}], "position": 1}, {"id": "ae4c8fb5-0e5a-4fbb-bbb3-e9f61f874628", "class": "col-lg-9 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "text-right", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "ae4c8fb5-0e5a-4fbb-bbb3-e9f61f874628", "attached_element_id": "6cceda6e-2654-42c0-8943-ac8ac9d5b489"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "toggle": false, "controls": [], "is_field": false, "settings": true, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "49b9ad57-0d51-4d1a-a091-072cefcce506", "name": "Single Column", "child": [{"id": "3c3a94de-6f3a-4d98-af8c-c3becc3a2bf9", "class": "col-lg-12 border-right", "controls": [], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "settings": "show", "droppable": false, "decoration": "1", "visibility": false}, {"id": "205bb944-4333-4add-baa7-5ae17bf238a9", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "205bb944-4333-4add-baa7-5ae17bf238a9"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2", "visibility": false}, {"id": "44ee34b7-4ab7-44da-9134-9994259f85d0", "name": "Two Column", "child": [{"id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "class": "col-lg-3", "controls": [{"id": "2e77e288-76dc-4e71-9f7c-b4f75b0c4890", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "0ac510f6-44c7-45cb-8d38-44e022c57ec0", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 1}, {"id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "4ac79f88-cd5d-433b-a64c-a85c3b9ff52e", "attached_element_id": "44ee34b7-4ab7-44da-9134-9994259f85d0"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2", "visibility": false}, {"id": "8c74b0d3-f439-4383-a442-e4386fb957d9", "name": "Single Column", "child": [{"id": "7142a607-6e91-4d36-8c73-6432feddd182", "class": "col-lg-12", "controls": [{"id": "ae1e9dc9-9458-4172-a2b7-6086a29ce1b0", "css": null, "src": null, "name": "h-1", "show": true, "type": "hr", "class": "blue", "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "7142a607-6e91-4d36-8c73-6432feddd182", "attached_element_id": "8c74b0d3-f439-4383-a442-e4386fb957d9"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1", "visibility": false}, {"id": "a8661be5-1b81-43ad-a614-2fefc2db80eb", "name": "Single Column", "child": [{"id": "88647d86-d061-43d5-9078-1e232b5ea47e", "class": "col-lg-12", "controls": [{"id": "0b2402a3-38de-47f8-a0f6-83b7c3cc3764", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Quotation Summary", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "88647d86-d061-43d5-9078-1e232b5ea47e", "attached_element_id": "a8661be5-1b81-43ad-a614-2fefc2db80eb"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1", "visibility": false}, {"id": "e1b21561-9910-4375-b324-bbee46c8bbf4", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Number:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "quotation_number", "name": "quotation_number", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "RFQ Number", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "e1b21561-9910-4375-b324-bbee46c8bbf4"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "344e5c52-2caf-4376-a4b3-915ca7a12965", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Quotation Valid Until:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "valid_until", "name": "valid_until", "show": true, "type": "tag", "class": "s4-form-control text-nowrap", "label": "Quotation Closing Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "344e5c52-2caf-4376-a4b3-915ca7a12965"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b", "hide": true, "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support Start Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}, {"id": "support_start_assumed_char", "css": null, "name": "support_start_assumed_char", "show": false, "type": "tag", "class": "s4-form-control", "label": "Support Start Assumed (*)", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a931eb6b-51e1-45d5-816e-dcdd64ec843b"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": true}, {"id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73", "hide": true, "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Support End Date:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}, {"id": "support_end_assumed_char", "css": null, "name": "support_end_assumed_char", "show": false, "type": "tag", "class": "s4-form-control", "label": "Support End Assumed (*)", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "7a98ee0b-e097-4370-938a-dc7d3e1f4f73"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": true}, {"id": "e8ea5a34-5838-461d-83f8-b2bab0a70a96", "name": "Two Column", "child": [{"id": "a2477129-bbd2-4187-81cd-6cf8ac5e8752", "class": "col-lg-4", "controls": [{"id": "e932d11b-f75c-44c2-b3a8-2c451a6ddc6f", "css": null, "src": null, "name": "l-1", "show": false, "type": "label", "class": "bold", "label": "Label", "value": "Support Duration:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "a2477129-bbd2-4187-81cd-6cf8ac5e8752", "attached_element_id": "e8ea5a34-5838-461d-83f8-b2bab0a70a96"}], "position": 1}, {"id": "cff6ed9a-3add-4bb8-81d3-c04da079206b", "class": "col-lg-8 border-right", "controls": [{"id": "contract_duration", "css": null, "name": "contract_duration", "show": false, "type": "tag", "class": "s4-form-control", "label": "Contract Duration", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "cff6ed9a-3add-4bb8-81d3-c04da079206b", "attached_element_id": "e8ea5a34-5838-461d-83f8-b2bab0a70a96"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "List Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "list_price", "name": "list_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total List Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "bb9ea3cc-95eb-4be1-b78d-7565b68c78cf"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "a7623070-c87b-428f-a894-87ccfcb909c2", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Applicable Discounts:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "applicable_discounts", "name": "applicable_discounts", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Total Discounts", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "a7623070-c87b-428f-a894-87ccfcb909c2"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Final Price:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "final_price", "name": "final_price", "type": "tag", "class": "s4-form-control text-nowrap", "label": "Final Price", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}, {"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "(All prices are excluding applicable taxes)", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "b6747784-7b91-4ca8-93fc-1ec0e7399f98"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce", "name": "Two Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Payment Terms:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-8", "controls": [{"id": "payment_terms", "css": null, "name": "payment_terms", "show": false, "type": "tag", "class": "s4-form-control", "label": "Payment Terms", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "6d38b26d-e1f4-4db2-bc87-4d6f4350f4ce"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "1+3", "visibility": false}, {"id": "9c378e04-49eb-472d-bead-5a02d628303b", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "h-1", "show": true, "type": "h", "class": "blue", "label": "Heading", "value": "Contact Us", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "9c378e04-49eb-472d-bead-5a02d628303b"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1", "visibility": false}, {"id": "eb4410df-3cb1-4e68-866a-f294658768b7", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Tel:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "+44 (0)113 887 8650", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "eb4410df-3cb1-4e68-866a-f294658768b7"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1", "visibility": false}, {"id": "5591650b-9c22-4f55-a286-916b64aa5588", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Email:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "sales@epd-worldwide.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "5591650b-9c22-4f55-a286-916b64aa5588"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1", "visibility": false}, {"id": "fef1dba7-386f-4959-bd7d-121de4a0cd54", "name": "Three Column", "child": [{"id": "2521a5c6-6eda-4420-876d-b821841f892e", "class": "col-lg-4", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "show": true, "type": "label", "class": "bold", "label": "Label", "value": "Visit:", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "2521a5c6-6eda-4420-876d-b821841f892e", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 1}, {"id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "class": "col-lg-6", "controls": [{"id": "c0093711-ea9d-478d-b288-8433ef02af26", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "www.europlusdirect.com", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "49e0ebf9-8c4d-422b-8641-7d5622e6f087", "attached_element_id": "fef1dba7-386f-4959-bd7d-121de4a0cd54"}], "position": 2}, {"id": "aefea0d8-c143-403f-ad55-207ae5432481", "class": "col-lg-2", "controls": [], "position": 3}], "class": "three-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2+1", "visibility": false}, {"id": "91e18f80-92f5-4628-b6d3-b951da437f0f", "name": "Single Column", "child": [{"id": "75d642c7-049b-438d-9e3f-249ba51691fa", "class": "col-lg-12 border-right", "controls": [{"id": "footer_notes", "css": null, "name": "footer_notes", "show": false, "type": "tag", "class": "s4-form-control", "label": "Footer notes", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "75d642c7-049b-438d-9e3f-249ba51691fa", "attached_element_id": "91e18f80-92f5-4628-b6d3-b951da437f0f"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "toggle": true, "controls": [], "is_field": false, "droppable": false, "decoration": "1", "visibility": true}], "payment_page": [{"id": "20d0dd48-f3fc-4663-9fda-3b76e9e9d1e0", "name": "Single Column", "child": [{"id": "9fa820ea-1bca-4136-ae6a-cbf6723a7073", "class": "col-lg-12 border-right", "controls": [{"id": "logo_set_x3", "css": null, "name": "logo_set_x3", "show": false, "type": "tag", "class": "s4-form-control", "label": "Logo Set X3", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9fa820ea-1bca-4136-ae6a-cbf6723a7073", "attached_element_id": "20d0dd48-f3fc-4663-9fda-3b76e9e9d1e0"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "4915df8d-0480-4a10-b881-bc3f0e2cd110", "name": "Two Column", "child": [{"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-3", "controls": [{"id": "6be62079-0092-4fde-9d08-24bc1967e25f", "css": null, "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "Quotation for", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "4915df8d-0480-4a10-b881-bc3f0e2cd110"}], "position": 1}, {"id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "class": "col-lg-9", "controls": [{"id": "vendor_name", "css": null, "name": "vendor_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Vendor Full Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "24222f6f-b4e6-44f2-bd81-d8465a1f8b47", "attached_element_id": "eb1e8eb2-2616-455c-aa10-52578736687e"}], "position": 2}], "class": "two-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "c83dff46-b6ef-4553-bc69-775d3694a487", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "For", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "customer_name", "name": "customer_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Customer Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "c83dff46-b6ef-4553-bc69-775d3694a487"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "54682e57-49d0-47c0-bb51-44233fa41438", "name": "Two Column", "child": [{"id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "class": "col-lg-3", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "show": true, "type": "label", "class": "blue", "label": "Label", "value": "From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "176fea14-5e5a-41d2-aa98-bcc2e0f66b93", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 1}, {"id": "9ccab411-7583-4400-90bf-975e1601b9d4", "class": "col-lg-9", "controls": [{"id": "company_name", "name": "company_name", "show": true, "type": "tag", "class": "s4-form-control blue", "label": "Internal Company Name", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "9ccab411-7583-4400-90bf-975e1601b9d4", "attached_element_id": "54682e57-49d0-47c0-bb51-44233fa41438"}], "position": 2}], "class": "two-column field-dragger", "order": 3, "controls": [], "is_field": false, "droppable": false, "decoration": "2"}, {"id": "13612e0d-7a90-420f-88cc-73ea17046865", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "087c4ed7-f16b-4250-b025-69ec72801d2f", "src": null, "name": "h-1", "type": "hr", "class": null, "label": "Line", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "13612e0d-7a90-420f-88cc-73ea17046865"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}, {"id": "baadd19a-a0b7-491c-930b-e1325653c3ce", "name": "Single Column", "child": [{"id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "class": "col-lg-12", "controls": [{"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "Payment Schedule From", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_start", "name": "support_start", "type": "tag", "class": "s4-form-control", "label": "Support Start Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "f86c0d28-b074-4758-9295-43d8cbe1214b", "src": null, "name": "l-1", "type": "label", "class": null, "label": "Label", "value": "To", "is_field": true, "is_image": false, "droppable": false, "is_system": false, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}, {"id": "support_end", "name": "support_end", "type": "tag", "class": "s4-form-control", "label": "Support End Date", "value": null, "is_field": true, "is_image": false, "droppable": false, "is_system": true, "is_required": false, "attached_child_id": "04d9adf1-8257-4001-b6bf-6fbfc250532f", "attached_element_id": "baadd19a-a0b7-491c-930b-e1325653c3ce"}], "position": 1}], "class": "single-column field-dragger", "order": 1, "controls": [], "is_field": false, "droppable": false, "decoration": "1"}]}
TEMPLATE;

        $template->form_data = json_decode($templateSchema);
        $template->save();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        $wwQuote->activeVersion->update(['quote_template_id' => $template->getKey()]);

        /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
        $opportunity = $wwQuote->opportunity;

        $opportunity->update([
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 53,
        ]);

        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Hardware'])
        );
        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Software'])
        );

        $country = Country::query()->first();
        $vendor = Vendor::query()->where('is_system', true)->first();

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
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
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

        $mapping = $templateFields->mapWithKeys(function (string $id, string $name) use ($importableColumns) {
            return [$id => ['importable_column_id' => $importableColumns->get($name)]];
        });

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity, $mapping, $vendor) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

            $distribution->opportunitySupplier()->associate($supplier)->save();

            $distribution->vendors()->sync($vendor->getKey());

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

        $expectedFileName = $wwQuote->quote_number.'.pdf';

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/export')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"$expectedFileName\"");

//        @mkdir("storage/framework/testing/ww-quotes/", recursive: true);
//
//        file_put_contents("storage/framework/testing/ww-quotes/$expectedFileName", $response->content());
    }

    /**
     * Test an ability to mark an existing Worldwide Contract Quote as active.
     */
    public function testCanMarkWorldwideContractQuoteAsActive(): void
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT, 'activated_at' => null]);

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
     * Test an ability to mark an existing Worldwide Contract Quote as inactive.
     */
    public function testCanMarkWorldwideContractQuoteAsInactive(): void
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT, 'activated_at' => now()]);

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey())
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
     * Test an ability to download all distributor files of an existing worldwide contract quote.
     *
     * @throws \Exception
     */
    public function testCanDownloadDistributorFilesOfWorldwideContractQuote(): void
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

        $opportunity = Opportunity::factory()->create();

        factory(OpportunitySupplier::class, 3)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        // Perform initialize Worldwide Quote request.
        $response = $this->postJson('api/ww-quotes', [
            'opportunity_id' => $opportunity->getKey(),
            'contract_type' => 'contract',
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

        $quoteModelKey = $response->json('id');

        $response = $this->getJson('api/ww-quotes/'.$quoteModelKey.'?include[]=worldwide_distributions.distributor_file')
            ->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_distributions' => [
                    '*' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'worldwide_distributions');

        $distributionModelKeys = $response->json('worldwide_distributions.*.id');

        $file = UploadedFile::fake()
            ->createWithContent((string) Uuid::generate(4).'.pdf',
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[0].'/distributor-file', [
            'file' => $file,
        ])
//            ->dump()
            ->assertCreated();

        $file = UploadedFile::fake()
            ->createWithContent($duplicatedFileName = (string) Uuid::generate(4).'.pdf',
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[1].'/distributor-file', [
            'file' => $file,
        ])
            ->assertCreated();

        $file = UploadedFile::fake()
            ->createWithContent($duplicatedFileName,
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[2].'/distributor-file', [
            'file' => $file,
        ])
            ->assertCreated();

        $response = $this->getJson('api/ww-quotes/'.$quoteModelKey.'?include[]=worldwide_distributions.distributor_file')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_distributions' => [
                    '*' => [
                        'id', 'distributor_file' => [
                            'id', 'original_file_name',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(3, array_filter($response->json('worldwide_distributions.*.distributor_file')));

        $quoteNumber = $response->json('quote_number');

        $expectedFileName = $quoteNumber.'-distributor-files.zip';

        $response = $this->getJson('api/ww-quotes/'.$quoteModelKey.'/files/distributor-files')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertHeader('content-disposition', "attachment; filename=$expectedFileName");

//        $fakeStorage = Storage::persistentFake();
//
//        $fakeStorage->put($expectedFileName, $response->getContent());
    }

    /**
     * Test an ability to download all distributor files of an existing worldwide contract quote.
     *
     * @throws \Exception
     */
    public function testCanDownloadPaymentScheduleFilesOfWorldwideContractQuote(): void
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

        $opportunity = Opportunity::factory()->create();

        factory(OpportunitySupplier::class, 3)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        // Perform initialize Worldwide Quote request.
        $response = $this->postJson('api/ww-quotes', [
            'opportunity_id' => $opportunity->getKey(),
            'contract_type' => 'contract',
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

        $quoteModelKey = $response->json('id');

        $response = $this->getJson('api/ww-quotes/'.$quoteModelKey.'?include[]=worldwide_distributions.schedule_file')
            ->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_distributions' => [
                    '*' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'worldwide_distributions');

        $distributionModelKeys = $response->json('worldwide_distributions.*.id');

        $file = UploadedFile::fake()
            ->createWithContent((string) Uuid::generate(4).'.pdf',
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[0].'/schedule-file', [
            'file' => $file,
        ])
            ->assertCreated();

        $file = UploadedFile::fake()
            ->createWithContent($duplicatedFileName = (string) Uuid::generate(4).'.pdf',
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[1].'/schedule-file', [
            'file' => $file,
        ])
            ->assertCreated();

        $file = UploadedFile::fake()
            ->createWithContent($duplicatedFileName,
                file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$distributionModelKeys[2].'/schedule-file', [
            'file' => $file,
        ])
            ->assertCreated();

        $response = $this->getJson('api/ww-quotes/'.$quoteModelKey.'?include[]=worldwide_distributions.schedule_file')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_distributions' => [
                    '*' => [
                        'id', 'schedule_file' => [
                            'id', 'original_file_name',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(3, array_filter($response->json('worldwide_distributions.*.schedule_file')));

        $quoteNumber = $response->json('quote_number');

        $expectedFileName = $quoteNumber.'-payment-schedule-files.zip';

        $response = $this->get('api/ww-quotes/'.$quoteModelKey.'/files/schedule-files')
//            ->dumpHeaders()
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertHeader('content-disposition', "attachment; filename=$expectedFileName");
    }

    /**
     * Test an ability to view price summary of contract quote after country margin & tax.
     */
    public function testCanViewPriceSummaryOfContractQuoteAfterCountryMarginAndTax(): void
    {
        $vendor = Vendor::query()->first();
        $country = Country::query()->first();

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);

        $distributorFile->mappedRows()->create(
            [
                'quote_file_id' => $distributorFile->getKey(),
                'is_selected' => true,
                'price' => 3_000,
            ]
        );

        $opportunity = Opportunity::factory()->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
            'contract_type_id' => CT_CONTRACT,
        ]);

        $snDiscount = factory(SND::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 5,
        ]);

        $distributorQuotes = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
            'sn_discount_id' => $snDiscount->getKey(),
        ]);

        foreach ($distributorQuotes as $distributorQuote) {
            $distributorQuote->opportunitySupplier()->associate(
                factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()])
            );

            $distributorQuote->save();

            $distributorQuote->vendors()->sync($vendor);
        }

        $marginTaxDataOfDistributorQuotes = [
            [
                'worldwide_distribution_id' => $distributorQuotes[0]->getKey(),
                'margin_value' => 10,
                'tax_value' => 10,
                'index' => 0,
            ],
            [
                'worldwide_distribution_id' => $distributorQuotes[1]->getKey(),
                'margin_value' => 10,
                'tax_value' => 10,
                'index' => 1,
            ],
        ];

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$quote->getKey().'/contract/country-margin-tax-price-summary', [
            'worldwide_distributions' => $marginTaxDataOfDistributorQuotes,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'price_summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_total_price_excluding_tax',
                    'final_margin',
                ],
                'worldwide_distributions' => [
                    '*' => [
                        'worldwide_distribution_id',
                        'index',
                        'price_summary' => [
                            'total_price',
                            'buy_price',
                            'final_total_price',
                            'final_total_price_excluding_tax',
                            'final_margin',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'price_summary' => [
                    'total_price' => '6000.00',
                    'buy_price' => '4000.00',
                    'final_total_price' => '6506.49',
                    'final_total_price_excluding_tax' => '6486.49',
                    'final_margin' => '38.33',
                ],
            ]);

        $this->postJson('api/ww-distributions/margin', [
            'worldwide_distributions' => [
                [
                    'id' => $distributorQuotes[0]->getKey(),
                    'quote_type' => 'New',
                    'margin_method' => 'No Margin',
                    'margin_value' => 10,
                    'tax_value' => 10,
                ],
                [
                    'id' => $distributorQuotes[1]->getKey(),
                    'quote_type' => 'New',
                    'margin_method' => 'No Margin',
                    'margin_value' => 10,
                    'tax_value' => 10,
                ],
            ],
            'stage' => 'Margin',
        ])
//                ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include=summary')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_total_price_excluding_tax',
                    'final_margin',
                ],
            ]);

        $this->assertEquals('6000.00', $response->json('summary.total_price'));
        $this->assertEquals('4000.00', $response->json('summary.buy_price'));
        $this->assertEquals('6506.49', $response->json('summary.final_total_price'));
        $this->assertEquals('6486.49', $response->json('summary.final_total_price_excluding_tax'));
        $this->assertEquals('572.34', $response->json('summary.applicable_discounts_value'));
        $this->assertEquals('38.33', $response->json('summary.final_margin'));
    }

    /**
     * Test an ability to view primary summary of contract quote after discounts.
     */
    public function testCanViewPriceSummaryOfContractQuoteAfterDiscounts(): void
    {
        $vendor = Vendor::query()->first();
        $country = Country::query()->first();

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);

        $distributorFile->mappedRows()->create(
            [
                'quote_file_id' => $distributorFile->getKey(),
                'is_selected' => true,
                'price' => 3_000,
            ]
        );

        $opportunity = Opportunity::factory()->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
            'contract_type_id' => CT_CONTRACT,
        ]);

        $snDiscount = factory(SND::class)->create([
            'vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 5,
        ]);

        /** @var \App\Domain\Worldwide\Models\WorldwideDistribution[] $distributorQuotes */
        $distributorQuotes = factory(WorldwideDistribution::class, 2)->create([
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
//            'sn_discount_id' => $snDiscount->getKey(),
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
        ]);

        foreach ($distributorQuotes as $distributorQuote) {
            $distributorQuote->opportunitySupplier()->associate(
                factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()])
            );

            $distributorQuote->save();

            $distributorQuote->vendors()->sync($vendor);
        }

        $discountsData = [
            [
                'worldwide_distribution_id' => $distributorQuotes[0]->getKey(),
                'index' => 0,
                'custom_discount' => 10.00,
            ],
            [
                'worldwide_distribution_id' => $distributorQuotes[1]->getKey(),
                'index' => 1,
                'predefined_discounts' => [
                    'sn_discount' => $snDiscount->getKey(),
                ],
            ],
        ];

        $this->authenticateApi();

        $response = $this->postJson('api/ww-quotes/'.$quote->getKey().'/contract/discounts-price-summary', [
            'worldwide_distributions' => $discountsData,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_quote_id',
                'price_summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_total_price_excluding_tax',
                    'final_margin',
                ],
                'worldwide_distributions' => [
                    '*' => [
                        'worldwide_distribution_id',
                        'index',
                        'price_summary' => [
                            'total_price',
                            'buy_price',
                            'final_total_price',
                            'final_total_price_excluding_tax',
                            'final_margin',
                        ],
                    ],
                ],
            ])
//            ->dump()
            ->assertJson([
                'price_summary' => [
                    'total_price' => '6000.00',
                    'buy_price' => '4000.00',
                    'final_total_price' => '5399.39',
                    'final_total_price_excluding_tax' => '5399.39',
                    'applicable_discounts_value' => '600.61',
                    'final_margin' => '25.92',
                ],
            ]);

        // Apply discounts and ensure that the values are equal

        $this->postJson('api/ww-distributions/discounts', [
            'worldwide_distributions' => [
                [
                    'id' => $distributorQuotes[0]->getKey(),
                    'custom_discount' => 10.00,
                ],
                [
                    'id' => $distributorQuotes[1]->getKey(),
                    'predefined_discounts' => [
                        'sn_discount' => $snDiscount->getKey(),
                    ],
                ],
            ],

            'stage' => 'Discount',
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?include[]=summary')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'summary' => [
                    'total_price',
                    'buy_price',
                    'final_total_price',
                    'final_margin',
                ],
            ]);

        $this->assertEquals('6000.00', $response->json('summary.total_price'));
        $this->assertEquals('4000.00', $response->json('summary.buy_price'));
        $this->assertEquals('5399.39', $response->json('summary.final_total_price'));
        $this->assertEquals('5399.39', $response->json('summary.final_total_price_excluding_tax'));
        $this->assertEquals('600.61', $response->json('summary.applicable_discounts_value'));
        $this->assertEquals('25.92', $response->json('summary.final_margin'));
    }

    /**
     * Test an ability to delete worldwide contract quote with an existing sales order.
     */
    public function testCanNotDeleteWorldwideContractQuoteWithSalesOrder(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);
        $salesOrder = factory(SalesOrder::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->authenticateApi();

        $this->deleteJson('api/ww-quotes/'.$quote->getKey())
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to delete worldwide contract quote with an existing sales order.
     */
    public function testCanNotUnravelWorldwideContractQuoteWithSalesOrder(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);
        $salesOrder = factory(SalesOrder::class)->create(['worldwide_quote_id' => $quote->getKey()]);

        $this->authenticateApi();

        $this->patchJson('api/ww-quotes/'.$quote->getKey().'/unravel')
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to validate data of worldwide contract quote.
     */
    public function testCanValidateWorldwideContractQuote(): void
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['submitted_at' => now()]);

        /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
        $opportunity = $wwQuote->opportunity;

        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Hardware'])
        );
        $opportunity->primaryAccount->addresses()->syncWithoutDetaching(
            factory(\App\Domain\Address\Models\Address::class)->create(['address_type' => 'Software'])
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
                'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
                'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
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

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity, $mapping, $vendor) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

            $distribution->opportunitySupplier()->associate($supplier)->save();

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

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/validate')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'passes',
                'errors',
            ]);
    }

    /**
     * Test an ability to mark worldwide contract quote as 'dead'.
     */
    public function testCanMarkWorldwideContractQuoteAsDead(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

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
     */
    public function testCanMarkWorldwideContractQuoteAsAlive(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

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
     * Test an ability to switch active version of contract quote.
     */
    public function testCanSwitchActiveVersionOfContractQuote(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

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
     * Test an ability to delete an existing version of contract quote.
     */
    public function testCanDeleteExistingVersionOfContractQuote(): void
    {
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

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
     * Test an ability to delete an active version of contract quote.
     */
    public function testCanNotDeleteActiveVersionOfContractQuote(): void
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create(['contract_type_id' => CT_CONTRACT]);

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
     * Test an ability to update an existing opportunity of worldwide quote,
     * and see the newly populated distributor quotes.
     */
    public function testCanUpdateOpportunityOfWorldwideQuoteAndSeeNewlyPopulatedDistributorQuotes(): void
    {
        /** @var Company $primaryAccount */
        $primaryAccount = Company::factory()->create([
            'category' => 'External',
        ]);

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create([
            'contract_type_id' => CT_CONTRACT,
            'primary_account_id' => $primaryAccount->getKey(),
        ]);

        $this->authenticateApi();

        // Initialization of a new contact quote.
        $response = $this->postJson('api/ww-quotes', [
            'opportunity_id' => $opportunity->getKey(),
            'contract_type' => 'contract',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'opportunity_id',
            ]);

        $quoteEntityKey = $response->json('id');

        $this->assertNotEmpty($quoteEntityKey);

        $response = $this->getJson('api/ww-quotes/'.$quoteEntityKey.'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.opportunity_supplier',
                    'worldwide_distributions.country',
                    'worldwide_distributions.addresses',
                    'worldwide_distributions.contacts',
                ],
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'opportunity_supplier' => [
                            'id',
                            'supplier_name',
                            'country_name',
                        ],
                    ],
                ],
            ]);

        $this->assertEmpty($response->json('worldwide_distributions'));

        $supplierFieldValue = CustomFieldValue::factory()->for(
            CustomField::query()->where('field_name', 'opportunity_distributor1')->sole()
        )
            ->hasAttached(
                CustomFieldValue::factory()->for(
                    CustomField::query()->where('field_name', 'opportunity_country1')->sole(),
                ),
                relationship: 'allowedBy'
            )
            ->create();

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'pipeline_id' => PL_WWDP,
            'primary_account_id' => $primaryAccount->getKey(),
            'contract_type_id' => CT_CONTRACT,
            'opportunity_start_date' => $this->faker->dateTimeBetween('-1 year', '+2 years')->format('Y-m-d'),
            'opportunity_end_date' => $this->faker->dateTimeBetween('-1 year', '+2 years')->format('Y-m-d'),
            'opportunity_closing_date' => $this->faker->dateTimeBetween('-1 year', '+2 years')->format('Y-m-d'),
            'suppliers_grid' => [
                [
                    'id' => null,
                    'supplier_name' => $supplierName = $supplierFieldValue->field_value,
                    'contact_name' => $supplierContactName = $this->faker->name,
                    'country_name' => $supplierCountry = $supplierFieldValue->allowedBy->first()->field_value,
                ],
            ],
            'is_contract_duration_checked' => false,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quoteEntityKey.'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.opportunity_supplier',
                    'worldwide_distributions.country',
                    'worldwide_distributions.addresses',
                    'worldwide_distributions.contacts',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'opportunity_supplier' => [
                            'id',
                            'supplier_name',
                            'country_name',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions'));
        $this->assertSame($supplierName,
            $response->json('worldwide_distributions.0.opportunity_supplier.supplier_name'));
        $this->assertSame($supplierContactName,
            $response->json('worldwide_distributions.0.opportunity_supplier.contact_name'));
        $this->assertSame($supplierCountry,
            $response->json('worldwide_distributions.0.opportunity_supplier.country_name'));

        $vendors = factory(Vendor::class, 2)->create();

        $address = factory(Address::class)->create();
        $contact = Contact::factory()->create();

        // Creating new addresses & contacts for primary account.
        $this->patchJson('api/companies/'.$primaryAccount->getKey(), [
            'sales_unit_id' => SalesUnit::factory()->create()->getKey(),
            'name' => $primaryAccount->name,
            'type' => 'External',
            'category' => 'End User',
            'vat_type' => 'NO VAT',
            'source' => 'Pipeliner',
            'email' => $this->faker->email,
            'vendors' => $vendors->modelKeys(),
            'addresses' => [
                ['id' => $address->getKey(), 'is_default' => true],
            ],
            'contacts' => [
                ['id' => $contact->getKey(), 'is_default' => true],
            ],
        ])
//            ->dump()
            ->assertOk();

        // Updating the opportunity entity to trigger an event
        // supposed to populate new addresses & contacts to the existing distributor quotes.
        $responseOfOpportunity = $this->getJson('api/opportunities/'.$opportunity->getKey())
            ->assertJsonStructure([
                'primary_account_id',
                'contract_type_id',
                'opportunity_start_date',
                'opportunity_end_date',
                'opportunity_closing_date',
                'suppliers_grid' => [
                    '*' => ['id', 'supplier_name', 'contact_name', 'country_name'],
                ],
            ])
            ->assertOk();

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'pipeline_id' => PL_WWDP,
            'primary_account_id' => $responseOfOpportunity->json('primary_account_id'),
            'contract_type_id' => $responseOfOpportunity->json('contract_type_id'),
            'opportunity_start_date' => $responseOfOpportunity->json('opportunity_start_date'),
            'opportunity_end_date' => $responseOfOpportunity->json('opportunity_end_date'),
            'opportunity_closing_date' => $responseOfOpportunity->json('opportunity_closing_date'),
            'suppliers_grid' => $responseOfOpportunity->json('suppliers_grid'),
            'is_contract_duration_checked' => false,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quoteEntityKey.'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.opportunity_supplier',
                    'worldwide_distributions.country',
                    'worldwide_distributions.addresses',
                    'worldwide_distributions.contacts',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'opportunity_supplier' => [
                            'id',
                            'supplier_name',
                            'country_name',
                        ],
                        'addresses' => [
                            '*' => [
                                'id',
                            ],
                        ],
                        'contacts' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions'));
        $this->assertSame($supplierName,
            $response->json('worldwide_distributions.0.opportunity_supplier.supplier_name'));
        $this->assertSame($supplierContactName,
            $response->json('worldwide_distributions.0.opportunity_supplier.contact_name'));
        $this->assertSame($supplierCountry,
            $response->json('worldwide_distributions.0.opportunity_supplier.country_name'));

        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
        $this->assertCount(1, $response->json('worldwide_distributions.0.contacts'));

        // Updating the opportunity entity again
        // to ensure no duplicated data is populated.
        $responseOfOpportunity = $this->getJson('api/opportunities/'.$opportunity->getKey())
            ->assertJsonStructure([
                'primary_account_id',
                'contract_type_id',
                'opportunity_start_date',
                'opportunity_end_date',
                'opportunity_closing_date',
                'suppliers_grid' => [
                    '*' => ['id', 'supplier_name', 'contact_name', 'country_name'],
                ],
            ])
            ->assertOk();

        $this->patchJson('api/opportunities/'.$opportunity->getKey(), [
            'pipeline_id' => PL_WWDP,
            'primary_account_id' => $responseOfOpportunity->json('primary_account_id'),
            'contract_type_id' => $responseOfOpportunity->json('contract_type_id'),
            'opportunity_start_date' => $responseOfOpportunity->json('opportunity_start_date'),
            'opportunity_end_date' => $responseOfOpportunity->json('opportunity_end_date'),
            'opportunity_closing_date' => $responseOfOpportunity->json('opportunity_closing_date'),
            'suppliers_grid' => $responseOfOpportunity->json('suppliers_grid'),
            'is_contract_duration_checked' => false,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quoteEntityKey.'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.opportunity_supplier',
                    'worldwide_distributions.country',
                    'worldwide_distributions.addresses',
                    'worldwide_distributions.contacts',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'opportunity_supplier' => [
                            'id',
                            'supplier_name',
                            'country_name',
                        ],
                        'addresses' => [
                            '*' => [
                                'id',
                            ],
                        ],
                        'contacts' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('worldwide_distributions'));
        $this->assertSame($supplierName,
            $response->json('worldwide_distributions.0.opportunity_supplier.supplier_name'));
        $this->assertSame($supplierContactName,
            $response->json('worldwide_distributions.0.opportunity_supplier.contact_name'));
        $this->assertSame($supplierCountry,
            $response->json('worldwide_distributions.0.opportunity_supplier.country_name'));

        $this->assertCount(1, $response->json('worldwide_distributions.0.addresses'));
        $this->assertCount(1, $response->json('worldwide_distributions.0.contacts'));
    }

    /**
     * Test an ability to replicate an existing contract worldwide quote.
     */
    public function testCanReplicateContractWorldwideQuote(): void
    {
        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
            'submitted_at' => now(),
        ]);

        $note = Note::factory()
            ->hasAttached($quote, relationship: 'worldwideQuotesHaveNote')
            ->hasAttached($quote->activeVersion, relationship: 'worldwideQuoteVersionsHaveNote')
            ->create();

        $this->authenticateApi();

        $response = $this->putJson('api/ww-quotes/'.$quote->getKey().'/copy')
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
            ]);

        $replicatedQuoteKey = $response->json('id');

        $this->assertNotEmpty($replicatedQuoteKey);

        $response = $this->getJson('api/ww-quotes/'.$replicatedQuoteKey)
            ->assertJsonStructure([
                'id',
                'submitted_at',
            ])
            ->assertOk();

        $this->assertEmpty($response->json('submitted_at'));

        $response = $this->getJson('api/ww-quotes/drafted?order_by_created_at=desc')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                    ],
                ],
            ]);

        $this->assertSame($replicatedQuoteKey, $response->json('data.0.id'));
    }

    /**
     * Test an ability to explicitly create a new version of contract worldwide quote.
     */
    public function testCanCreateNewVersionOfContractWorldwideQuote(): void
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
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
     * Test an ability to explicitly create a new version of contract worldwide quote.
     */
    public function testCanCreateNewVersionOfContractWorldwideQuoteFromSpecifiedVersion(): void
    {
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
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
     * Test an ability to change quote currency,
     * and see the converted price values of mapped rows.
     */
    public function testCanSeeConvertedPricesOfMappedRowsWhenQuoteCurrencyIsChanged(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'contract_type_id' => CT_CONTRACT,
        ]);

        $quoteTemplate = factory(QuoteTemplate::class)->create();

        $quote->activeVersion->update([
            'user_id' => $this->app['auth.driver']->id(),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
        ]);

        $quoteFile = factory(QuoteFile::class)->create();

        $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var \App\Domain\Worldwide\Models\WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'distribution_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'distributor_file_id' => $quoteFile->getKey(),
            'opportunity_supplier_id' => $opportunitySupplier->getKey(),
        ]);

        $addresses = factory(Address::class, 2)->create();
        $contacts = Contact::factory()->count(2)->create();

        $mappedRows = factory(MappedRow::class, 2)->create([
            'quote_file_id' => $quoteFile->getKey(), 'original_price' => 1_000, 'price' => 1_000,
        ]);

        // Assert that price values are not changed if the currencies are the same.
        $this->postJson('api/ww-quotes/'.$quote->getKey().'/import', [
            'company_id' => Company::query()->where('short_code', 'EPD')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
            'quote_template_id' => $quoteTemplate->getKey(),
            'quote_expiry_date' => now()->toDateString(),
            'payment_terms' => '30 Days',
            'worldwide_distributions' => [
                [
                    'id' => $distributorQuote->getKey(),
                    'vendors' => [Vendor::query()->where('short_code', 'HPE')->value('id')],
                    'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
                    'distribution_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
                    'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
                    'distribution_expiry_date' => now()->toDateString(),
                    'addresses' => $addresses->modelKeys(),
                    'contacts' => $contacts->modelKeys(),
                    'buy_price' => 2_000,
                ],
            ],
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query(['include' => ['worldwide_distributions.mapped_rows']]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'mapped_rows' => [
                            '*' => ['id', 'price'],
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('worldwide_distributions.*.mapped_rows.*.price'));

        foreach ($response->json('worldwide_distributions.*.mapped_rows.*.price') as $priceValue) {
            $this->assertSame(1000, $priceValue);
        }

        // Assert that price values are updated once the currencies come different.
        $this->postJson('api/ww-quotes/'.$quote->getKey().'/import', [
            'company_id' => Company::query()->where('short_code', 'EPD')->value('id'),
            'quote_currency_id' => Currency::query()->where('code', 'USD')->value('id'),
            'quote_template_id' => $quoteTemplate->getKey(),
            'quote_expiry_date' => now()->toDateString(),
            'payment_terms' => '30 Days',
            'worldwide_distributions' => [
                [
                    'id' => $distributorQuote->getKey(),
                    'vendors' => [Vendor::query()->where('short_code', 'HPE')->value('id')],
                    'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
                    'distribution_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
                    'distribution_currency_quote_currency_exchange_rate_value' => 999999,
                    'buy_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
                    'distribution_expiry_date' => now()->toDateString(),
                    'addresses' => $addresses->modelKeys(),
                    'contacts' => $contacts->modelKeys(),
                    'buy_price' => 2_000,
                ],
            ],
            'stage' => 'Import',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/ww-quotes/'.$quote->getKey().'?'.Arr::query(['include' => ['worldwide_distributions.mapped_rows']]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'mapped_rows' => [
                            '*' => ['id', 'price'],
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('worldwide_distributions.*.mapped_rows.*.price'));

        foreach ($response->json('worldwide_distributions.*.mapped_rows.*.price') as $priceValue) {
            $this->assertNotSame(1_000, $priceValue);
        }
    }
}
