<?php

namespace Tests\Feature;

use App\Events\DistributionProcessed;
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
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class WorldwideDistributorQuoteTest
 * @group worldwide
 */
class WorldwideDistributorQuoteTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test ability to store distributor file in worldwide distribution.
     *
     * @return void
     */
    public function testCanStoreWorldwideDistributionDistributorFile()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $file = UploadedFile::fake()->createWithContent('dist-1.pdf', file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/distributor-file', [
            'file' => $file,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'pages',
                'original_file_path',
                'original_file_name',
                'format' => [
                    'id', 'name', 'extension',
                ],
                'created_at',
            ]);
    }

    /**
     * Test ability to store payment schedule file in worldwide distribution.
     *
     * @return void
     */
    public function testCanStoreWorldwideDistributionScheduleFile()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $file = UploadedFile::fake()->createWithContent('dist-1.pdf', file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/schedule-file', [
            'file' => $file,
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'pages',
                'original_file_path',
                'original_file_name',
                'format' => [
                    'id', 'name', 'extension',
                ],
                'created_at',
            ]);
    }


    /**
     * Test an ability to process worldwide distributions.
     *
     * @return void
     */
    public function testCanProcessWorldwideQuoteDistributions()
    {
        $this->authenticateApi();

        $storage = Storage::persistentFake();

        $quoteExpiryDate = now()->addMonth();

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwQuote->activeVersion->update(['quote_expiry_date' => $quoteExpiryDate]);

        $fileName = Str::random(40).'.pdf';

        $storage->put($fileName, file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

//        $storage->put($fileName, file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-invalid.xlsx')));

        /** @var QuoteFile $distributorFile */
        $distributorFile = tap(new QuoteFile([
            'original_file_path' => $fileName,
            'pages' => 7,
            'file_type' => 'Worldwide Distributor Price List',
            'quote_file_format_id' => QuoteFileFormat::query()->where('extension', 'pdf')->value('id'),
            'original_file_name' => $fileName,
            'imported_page' => 2,
        ]))->save();

        $wwDistribution = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
        ]);

        $distributionExpiryDate = $quoteExpiryDate->copy();

        $data = $wwDistribution->map(function (WorldwideDistribution $distribution) use ($distributionExpiryDate, $distributorFile) {
            return [
                'id' => $distribution->getKey(),
                'vendors' => Vendor::query()->whereIn('short_code', ['HPE', 'LEN', 'IBM'])->pluck('id')->all(),
                'country_id' => Country::query()->where('iso_3166_2', $this->faker->randomElement(['US', 'GB', 'AU']))->value('id'),
                'distributor_file_id' => $distributorFile->getKey(),
                'distributor_file_page' => null,
                'buy_currency_id' => Currency::query()->where('code', $this->faker->randomElement(['USD', 'GBP']))->value('id'),
                'distribution_currency_id' => Currency::query()->where('code', $this->faker->randomElement(['USD', 'GBP']))->value('id'),
                'distribution_expiry_date' => $distributionExpiryDate->addDay()->toDateString(),

                'addresses' => factory(Address::class, 2)->create()->modelKeys(),
                'contacts' => Contact::factory()->count(2)->create()->modelKeys(),

                'buy_price' => $this->faker->randomFloat(null, 1000, 999999),
                'calculate_list_price' => null,
            ];
        })->all();

        Event::fake([DistributionProcessed::class]);

//        $this->app[SettingRepository::class]->set('use_legacy_doc_parsing_method', true);

        $this->postJson('api/ww-distributions/handle', ['worldwide_distributions' => $data])
//            ->dump()
            ->assertOk()
            ->assertExactJson([true]);

        Event::assertDispatched(DistributionProcessed::class, 2);

        Event::assertDispatched(DistributionProcessed::class, function (DistributionProcessed $event) use ($wwQuote, $wwDistribution) {
            $this->assertArrayHasKey('distributor_file', $event->failures->toArray());
            $this->assertArrayHasKey('schedule_file', $event->failures->toArray());

            $this->assertEquals($event->quoteKey, $wwQuote->getKey());
            $this->assertContainsEquals($event->distributionKey, $wwDistribution->modelKeys());

            return true;
        });

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.mapping')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'distribution_expiry_date',
                    ],
                ],
            ]);


        $distributionsExpiryDate = Arr::pluck($data, 'distribution_expiry_date', 'id');

        foreach ($response->json('worldwide_distributions') as $distribution) {
            $this->assertEquals($distributionsExpiryDate[$distribution['replicated_distributor_quote_id']], $distribution['distribution_expiry_date']);
        }
    }

    /**
     * Test an ability to receive failures occurred during distributions process.
     *
     * @return void
     */
    public function testCanReceiveFailuresDuringDistributionsProcess()
    {
        $this->authenticateApi();

        $storage = Storage::persistentFake();

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $fileName = Str::random(40).'.xlsx';

        // File without any data.
        $storage->put($fileName, file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-invalid.xlsx')));

        $distributorFile = tap(new QuoteFile([
            'original_file_path' => $fileName,
            'pages' => 7,
            'file_type' => 'Distributor Price List',
            'quote_file_format_id' => QuoteFileFormat::where('extension', 'xlsx')->value('id'),
        ]))->save();

        $wwDistribution = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'distributor_file_id' => $distributorFile->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $data = $wwDistribution->map(function (WorldwideDistribution $distribution) use ($distributorFile) {
            return [
                'id' => $distribution->getKey(),
                'vendors' => Vendor::whereIn('short_code', ['HPE', 'LEN', 'IBM'])->pluck('id')->all(),
                'country_id' => Country::where('iso_3166_2', $this->faker->randomElement(['US', 'GB', 'AU']))->value('id'),
                'distributor_file_id' => $distributorFile->getKey(),
                'distributor_file_page' => null,
                'quote_template_id' => QuoteTemplate::value('id'),
                'buy_currency_id' => Currency::where('code', $this->faker->randomElement(['USD', 'GBP']))->value('id'),
                'distribution_currency_id' => Currency::where('code', $this->faker->randomElement(['USD', 'GBP']))->value('id'),
                'distribution_expiry_date' => now()->toDateString(),

                'addresses' => factory(Address::class, 2)->create()->modelKeys(),
                'contacts' => Contact::factory()->count(2)->create()->modelKeys(),

                'buy_price' => $this->faker->randomFloat(null, 1000, 999999),
                'calculate_list_price' => null,
            ];
        })->all();

        Event::fake([DistributionProcessed::class]);

        $this->postJson('api/ww-distributions/handle', ['worldwide_distributions' => $data])
//            ->dump()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
            ]);

        Event::assertDispatched(DistributionProcessed::class, 2);

        Event::assertDispatched(DistributionProcessed::class, function (DistributionProcessed $event) use ($wwQuote, $wwDistribution) {
            $this->assertNotEmpty(
                $event->failures->get('distributor_file')
            );

            $this->assertEquals($event->quoteKey, $wwQuote->getKey());
            $this->assertContainsEquals($event->distributionKey, $wwDistribution->modelKeys());

            return true;
        });
    }

    /**
     * Test an ability to update worldwide distributions mapping.
     *
     * @return void
     */
    public function testCanUpdateWorldwideDistributionsMapping()
    {
        $this->authenticateApi();

        $storage = Storage::persistentFake();

        $quoteCurrency = Currency::query()->where('code', 'GBP')->first();

        $distributionCurrency = Currency::query()->where('code', 'USD')->first();

        tap(new ExchangeRate, function (ExchangeRate $exchangeRate) use ($distributionCurrency) {
            $exchangeRate->currency_code = 'USD';
            $exchangeRate->currency_id = $distributionCurrency->getKey();
            $exchangeRate->save();
        });

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $wwQuote->activeVersion->update([
            'quote_currency_id' => $quoteCurrency->getKey(),
        ]);

        $fileName = Str::random(40).'.pdf';

        $storage->put($fileName, file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $distributorFile = tap(new QuoteFile([
            'original_file_path' => $fileName,
            'pages' => 7,
            'file_type' => 'Distributor Price List',
            'quote_file_format_id' => QuoteFileFormat::where('extension', 'pdf')->value('id'),
            'imported_page' => 2,
        ]))->save();

        factory(ImportedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'distributor_file_id' => $distributorFile->getKey(),
            'distribution_currency_id' => $distributionCurrency->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $opportunity = Opportunity::factory()->create();

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');

        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $fieldsMapping = $templateFields->map(function (string $id, string $name) use ($importableColumns) {
            return [
                'template_field_id' => $id,
                'importable_column_id' => $importableColumns->get($name) ?? factory(ImportableColumn::class)->create()->getKey(),
            ];
        })
            ->values()
            ->all();

        $distributorQuotesMapping = $wwDistributions->map(fn(WorldwideDistribution $distribution) => [
            'id' => $distribution->getKey(),
            'mapping' => $fieldsMapping,
        ])->all();

        $this->postJson('api/ww-distributions/mapping', ['worldwide_distributions' => $distributorQuotesMapping, 'stage' => 'Mapping'])
            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.mapping',
                    'worldwide_distributions.mapped_rows',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapped_rows' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $firstMappedRowKey = $response->json('worldwide_distributions.0.mapped_rows.0.id');

        $this->assertEquals('Mapping', $response->json('stage'));

        $distributionsResponse = $response->json('worldwide_distributions');

        $expectedMapping = collect($distributorQuotesMapping)->keyBy('id');

        foreach ($distributionsResponse as $distribution) {
            $this->assertArrayHasKey('id', $distribution);

            $this->assertArrayHasKey($distribution['replicated_distributor_quote_id'], $expectedMapping);

            $distributionMapping = array_map(function (array $mapping) {
                return Arr::only($mapping, ['template_field_id', 'importable_column_id']);
            }, $distribution['mapping']);

            $expectedDistributionMapping = $expectedMapping[$distribution['replicated_distributor_quote_id']]['mapping'];

            foreach ($expectedDistributionMapping as $field) {
                $this->assertContainsEquals($field, $distributionMapping);
            }
        }

        $distributorQuoteDictionary = collect($distributionsResponse)->pluck('id', 'replicated_distributor_quote_id');

        // Make one column as editable for each distributor quote.
        // The rows should not be mapped again in this case.
        $newMapping = [];

        foreach ($distributorQuotesMapping as $distributorQuoteMapping) {
            $distributorQuoteMapping['mapping'][0]['is_editable'] = true;

            // Since a new version is created, we have to update entity keys accordingly.
            $distributorQuoteMapping['id'] = $distributorQuoteDictionary[$distributorQuoteMapping['id']];

            $newMapping[] = $distributorQuoteMapping;
        }

        $this->postJson('api/ww-distributions/mapping', ['worldwide_distributions' => $newMapping, 'stage' => 'Mapping'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.mapping',
                    'worldwide_distributions.mapped_rows',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapped_rows' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertSame($firstMappedRowKey, $response->json('worldwide_distributions.0.mapped_rows.0.id'));
    }

    /**
     * Test an ability to update worldwide distributions mapping.
     *
     * @return void
     */
    public function testCanUpdateWorldwideDistributionsMappingWithSetCustomFileDateFormat()
    {
        $this->authenticateApi();

        $storage = Storage::persistentFake();

        $quoteCurrency = Currency::query()->where('code', 'GBP')->first();

        $distributionCurrency = Currency::query()->where('code', 'USD')->first();

        tap(new ExchangeRate, function (ExchangeRate $exchangeRate) use ($distributionCurrency) {
            $exchangeRate->currency_code = 'USD';
            $exchangeRate->currency_id = $distributionCurrency->getKey();
            $exchangeRate->save();
        });

        $opportunity = factory(Opportunity::class)->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $wwQuote->activeVersion->update([
            'quote_currency_id' => $quoteCurrency->getKey(),
        ]);

        $fileName = Str::random(40).'.pdf';

        $storage->put($fileName, file_get_contents(base_path('tests/Feature/Data/distributor-files/dist-1.pdf')));

        $distributorFile = tap(new QuoteFile([
            'original_file_path' => $fileName,
            'pages' => 7,
            'file_type' => 'Distributor Price List',
            'quote_file_format_id' => QuoteFileFormat::where('extension', 'pdf')->value('id'),
            'imported_page' => 2,
        ]))->save();

        factory(ImportedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'distributor_file_id' => $distributorFile->getKey(),
            'distribution_currency_id' => $distributionCurrency->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'file_date_format' => 'MM/DD/YYYY'
        ]);

        $opportunity = factory(Opportunity::class)->create();

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');

        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $fieldsMapping = $templateFields->map(function (string $id, string $name) use ($importableColumns) {
            return [
                'template_field_id' => $id,
                'importable_column_id' => $importableColumns->get($name) ?? factory(ImportableColumn::class)->create()->getKey(),
            ];
        })
            ->values()
            ->all();

        $distributorQuotesMapping = $wwDistributions->map(fn(WorldwideDistribution $distribution) => [
            'id' => $distribution->getKey(),
            'mapping' => $fieldsMapping,
        ])->all();

        $this->postJson('api/ww-distributions/mapping', ['worldwide_distributions' => $distributorQuotesMapping, 'stage' => 'Mapping'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.mapping',
                    'worldwide_distributions.mapped_rows',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapped_rows' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $firstMappedRowKey = $response->json('worldwide_distributions.0.mapped_rows.0.id');

        $this->assertEquals('Mapping', $response->json('stage'));

        $distributionsResponse = $response->json('worldwide_distributions');

        $expectedMapping = collect($distributorQuotesMapping)->keyBy('id');

        foreach ($distributionsResponse as $distribution) {
            $this->assertArrayHasKey('id', $distribution);

            $this->assertArrayHasKey($distribution['replicated_distributor_quote_id'], $expectedMapping);

            $distributionMapping = array_map(function (array $mapping) {
                return Arr::only($mapping, ['template_field_id', 'importable_column_id']);
            }, $distribution['mapping']);

            $expectedDistributionMapping = $expectedMapping[$distribution['replicated_distributor_quote_id']]['mapping'];

            foreach ($expectedDistributionMapping as $field) {
                $this->assertContainsEquals($field, $distributionMapping);
            }
        }

        $distributorQuoteDictionary = collect($distributionsResponse)->pluck('id', 'replicated_distributor_quote_id');

        // Make one column as editable for each distributor quote.
        // The rows should not be mapped again in this case.
        $newMapping = [];

        foreach ($distributorQuotesMapping as $distributorQuoteMapping) {
            $distributorQuoteMapping['mapping'][0]['is_editable'] = true;

            // Since a new version is created, we have to update entity keys accordingly.
            $distributorQuoteMapping['id'] = $distributorQuoteDictionary[$distributorQuoteMapping['id']];

            $newMapping[] = $distributorQuoteMapping;
        }

        $this->postJson('api/ww-distributions/mapping', ['worldwide_distributions' => $newMapping, 'stage' => 'Mapping'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?'.Arr::query([
                'include' => [
                    'worldwide_distributions.mapping',
                    'worldwide_distributions.mapped_rows',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapped_rows' => [
                            '*' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertSame($firstMappedRowKey, $response->json('worldwide_distributions.0.mapped_rows.0.id'));
    }

    /**
     * Test an ability to set worldwide distributions margin.
     *
     * @return void
     */
    public function testCanSetWorldwideDistributionsMargin()
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwDistributionWithMargin = factory(WorldwideDistribution::class)->create(['worldwide_quote_id' => $wwQuote->activeVersion->getKey(), 'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass()]);
        $wwDistributionWithoutMargin = factory(WorldwideDistribution::class)->create(['worldwide_quote_id' => $wwQuote->activeVersion->getKey(), 'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass()]);

        $opportunity = Opportunity::factory()->create();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
        $wwDistributionWithMargin->opportunitySupplier()->associate($supplier);
        $wwDistributionWithMargin->save();

        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
        $wwDistributionWithoutMargin->opportunitySupplier()->associate($supplier);
        $wwDistributionWithoutMargin->save();

        $marginData = [
            'worldwide_distributions' => [
                [
                    'id' => $wwDistributionWithMargin->getKey(),
                    'margin_value' => 10.00,
                    'tax_value' => 5.00,
                    'margin_method' => 'No Margin',
                    'quote_type' => 'New',
                ],
                [
                    'id' => $wwDistributionWithoutMargin->getKey(),
                    'margin_value' => null,
                    'margin_method' => null,
                    'quote_type' => null,
                ],
            ],
            'stage' => 'Margin',
        ];

        $this->postJson('api/ww-distributions/margin', $marginData)
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions')->assertOk();

        $this->assertEquals('Margin', $response->json('stage'));

        $distributionsResponse = $response->json('worldwide_distributions');

        $distributionWithMarginResponse = Arr::first($distributionsResponse, fn(array $distr) => $distr['replicated_distributor_quote_id'] === $wwDistributionWithMargin->getKey());
        $distributionWithoutMarginResponse = Arr::first($distributionsResponse, fn(array $distr) => $distr['replicated_distributor_quote_id'] === $wwDistributionWithoutMargin->getKey());

        $this->assertIsArray($distributionWithMarginResponse);
        $this->assertIsArray($distributionWithoutMarginResponse);

        $this->assertEquals($marginData['worldwide_distributions'][0]['margin_value'], $distributionWithMarginResponse['margin_value']);
        $this->assertEquals($marginData['worldwide_distributions'][0]['margin_method'], $distributionWithMarginResponse['margin_method']);
        $this->assertEquals($marginData['worldwide_distributions'][0]['quote_type'], $distributionWithMarginResponse['quote_type']);
        $this->assertEquals($marginData['worldwide_distributions'][0]['tax_value'], $distributionWithMarginResponse['tax_value']);

        $this->assertNull($distributionWithoutMarginResponse['margin_value']);
        $this->assertNull($distributionWithoutMarginResponse['margin_method']);
        $this->assertNull($distributionWithoutMarginResponse['quote_type']);
        $this->assertNull($distributionWithoutMarginResponse['tax_value']);
    }


    /**
     * Test an ability to update worldwide distributions selected rows.
     *
     * @return void
     */
    public function testCanUpdateWorldwideDistributionsSelectedRows()
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $country = Country::query()->first();
        $vendors = Vendor::query()->limit(2)->get();
        $template = factory(QuoteTemplate::class)->create();

        $opportunity = Opportunity::factory()->create();

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'country_id' => $country->getKey(),
        ])->each(function (WorldwideDistribution $distribution) use ($vendors, $opportunity) {
            $distribution->vendors()->sync($vendors);
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $wwDistributions->each(function (WorldwideDistribution $distribution) {
            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);
            $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $distribution->setRelation('mappedRows', $mappedRows);
        });

        $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistributions[1]->getKey()]);
        $rowsGroup->rows()->sync($wwDistributions[1]->mappedRows);

        $selection = collect([
            [
                'id' => $wwDistributions[0]->getKey(),
                'selected_rows' => $wwDistributions[0]->mappedRows->random(2)->modelKeys(),
                'selected_groups' => [],
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'reject' => false,
                'use_groups' => false,
            ],
            [
                'id' => $wwDistributions[1]->getKey(),
                'selected_rows' => [],
                'selected_groups' => [$rowsGroup->getKey()],
                'sort_rows_groups_column' => 'group_name',
                'sort_rows_groups_direction' => 'desc',
                'reject' => false,
                'use_groups' => true,
            ],
        ]);

        $this->postJson('api/ww-distributions/mapping-review', ['worldwide_distributions' => $selection->all(), 'stage' => 'Review'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.mapped_rows')
            ->assertOk()
            ->assertJsonStructure([
                'stage',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'mapped_rows' => [
                            '*' => [
                                'id', 'is_selected',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('Review', $response->json('stage'));

        $selection = $selection->keyBy('id');

        foreach ($response->json('worldwide_distributions') as $distr) {
            $this->assertArrayHasKey($distr['replicated_distributor_quote_id'], $selection);

            $payloadOfDistr = $selection[$distr['replicated_distributor_quote_id']];

            $distrSelectedRows = collect($distr['mapped_rows'])->where('is_selected', true)->pluck('replicated_mapped_row_id')->sort()->values();

            sort($payloadOfDistr['selected_rows']);

            $this->assertEquals($distrSelectedRows->all(), $payloadOfDistr['selected_rows']);
        }
    }

    /**
     * Test mapping review request validation.
     *
     * @return void
     */
    public function testCanValidateMappingReviewRequest()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $country = Country::query()->first();
        $vendors = Vendor::query()->limit(2)->get();
        $template = factory(QuoteTemplate::class)->create();

        $wwDistributions = factory(WorldwideDistribution::class, 2)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'country_id' => $country->getKey(),
        ])->each(function (WorldwideDistribution $distribution) use ($vendors) {
            $distribution->vendors()->sync($vendors);
        });

        $wwDistributions->each(function (WorldwideDistribution $distribution) {
            $distributorFile = factory(QuoteFile::class)->create([
                'file_type' => QFT_WWPL,
            ]);
            $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

            $distribution->update(['distributor_file_id' => $distributorFile->getKey()]);

            $distribution->setRelation('mappedRows', $mappedRows);
        });


        $selection = $wwDistributions->map(function (WorldwideDistribution $distribution) {
            return [
                'id' => $distribution->getKey(),
                'selected_rows' => $distribution->mappedRows->random(2)->modelKeys(),
                'sort_rows_column' => 'product_no',
                'sort_rows_direction' => 'asc',
                'reject' => $this->faker->boolean(),
                'use_groups' => true,
            ];
        })->all();

        $this->postJson('api/ww-distributions/mapping-review', ['worldwide_distributions' => $selection, 'stage' => 'Review'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'worldwide_distributions.0.use_groups' => 'must contain one group at least to use grouping',
            ], 'Error.original');
    }

    /**
     * Test an ability to create a new rows group of worldwide distribution.
     *
     * @return void
     */
    public function testCanCreateRowsGroupOfWorldwideDistribution()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);
        $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

        $wwDistribution->update(['distributor_file_id' => $distributorFile->getKey()]);

        $groupData = [
            'group_name' => Str::random(40),
            'search_text' => Str::random(40),
            'rows' => $mappedRows->random(2)->modelKeys(),
        ];

        $response = $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/rows-groups', $groupData)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_distribution_id',
                'rows_sum',
                'rows_count',
                'rows' => [
                    '*' => ['id', 'is_customer_exclusive_asset'],
                ],
                'group_name',
                'search_text',
                'is_selected',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotNull($response->json('rows_sum'));
        $this->assertNotNull($response->json('rows_count'));
    }

    /**
     * Test an ability to lookup rows of worldwide distribution.
     *
     * @return void
     */
    public function testCanLookupRowsGroupOfWorldwideDistribution()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);
        $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

        $mappedRows->push(factory(MappedRow::class)->create(['quote_file_id' => $distributorFile->getKey(), 'product_no' => '818208-B21']));

        $wwDistribution->update(['distributor_file_id' => $distributorFile->getKey()]);

        $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistribution->getKey()]);

        $rowsGroup->rows()->sync($mappedRows->modelKeys());

        $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/rows-lookup', [
            'input' => '818208-B21, CZJ8170VHN',
            'rows_group_id' => $rowsGroup->getKey(),
        ])
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
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
            ]);
    }

    /**
     * Test an ability to update a newly created rows group of worldwide distribution.
     *
     * @return void
     */
    public function testCanUpdateRowsGroupOfWorldwideDistribution()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);


        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);
        $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

        $wwDistribution->update(['distributor_file_id' => $distributorFile->getKey()]);

        $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistribution->getKey()]);

        $groupData = [
            'group_name' => Str::random(40),
            'search_text' => Str::random(40),
            'rows' => $mappedRows->random(2)->modelKeys(),
        ];

        $response = $this->patchJson('api/ww-distributions/'.$wwDistribution->getKey().'/rows-groups/'.$rowsGroup->getKey(), $groupData)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'worldwide_distribution_id',
                'rows' => [
                    '*' => ['id', 'is_customer_exclusive_asset'],
                ],
                'rows_sum',
                'rows_count',
                'group_name',
                'search_text',
                'is_selected',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotNull($response->json('rows_sum'));
        $this->assertNotNull($response->json('rows_count'));
    }


    /**
     * Test an ability to delete a newly created rows group of worldwide distribution.
     *
     * @return void
     */
    public function testCanDeleteRowsGroupOfWorldwideDistribution()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);


        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);
        $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

        $wwDistribution->update(['distributor_file_id' => $distributorFile->getKey()]);

        $rowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistribution->getKey()]);

        $this->deleteJson('api/ww-distributions/'.$wwDistribution->getKey().'/rows-groups/'.$rowsGroup->getKey())
            ->assertNoContent();
    }

    /**
     * Test an ability to move rows between groups of worldwide distribution.
     *
     * @return void
     */
    public function testCanMoveRowsBetweenGroupsOfWorldwideDistribution()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);


        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);
        $mappedRows = factory(MappedRow::class, 10)->create(['quote_file_id' => $distributorFile->getKey()]);

        $wwDistribution->update(['distributor_file_id' => $distributorFile->getKey()]);

        $outputRowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistribution->getKey()]);
        $outputRowsGroup->rows()->sync($mappedRows);

        $inputRowsGroup = factory(DistributionRowsGroup::class)->create(['worldwide_distribution_id' => $wwDistribution->getKey()]);

        $this->putJson('api/ww-distributions/'.$wwDistribution->getKey().'/rows-groups', [
            'output_rows_group_id' => $outputRowsGroup->getKey(),
            'rows' => $mappedRows->modelKeys(),
            'input_rows_group_id' => $inputRowsGroup->getKey(),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'output_rows_group' => [
                    'id',
                    'worldwide_distribution_id',
                    'rows' => [
                        '*' => ['id', 'is_customer_exclusive_asset'],
                    ],
                    'rows_sum',
                    'rows_count',
                    'group_name',
                    'search_text',
                    'is_selected',
                    'created_at',
                    'updated_at',
                ],
                'input_rows_group' => [
                    'id',
                    'worldwide_distribution_id',
                    'rows' => [
                        '*' => ['id', 'is_customer_exclusive_asset'],
                    ],
                    'rows_sum',
                    'rows_count',
                    'group_name',
                    'search_text',
                    'is_selected',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // TODO: test that rows are moved to another group.
    }

    public function testCanViewDistributionApplicableDiscounts()
    {
        $vendor = Vendor::query()->first();
        $country = Country::query()->first();

        $wwDistribution = factory(WorldwideDistribution::class)->create(['country_id' => $country->getKey()]);

        $wwDistribution->vendors()->sync($vendor);

        $this->authenticateApi();

        factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);
        factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey()]);

        $this->getJson('api/ww-distributions/'.$wwDistribution->getKey().'/applicable-discounts')
            ->assertOk()
            ->assertJsonStructure([
                'pre_pay' => [
                    '*' => [
                        'id', 'name', 'durations' => ['duration' => ['value', 'duration']],
                    ],
                ],
                'multi_year' => [
                    '*' => [
                        'id', 'name', 'durations' => ['duration' => ['value', 'duration']],
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
    }

    public function testCanViewCalculatedDistributionMarginValueAfterSpecialDiscounts()
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

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
            'margin_value' => 10,
        ]);

        $wwDistribution->vendors()->sync($vendor);

        $this->authenticateApi();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 5]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 12, 'minimum_limit' => 1000]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
            'durations' => [
                'duration' => ['value' => 3, 'duration' => 3],
            ],
        ]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
            'durations' => [
                'duration' => ['value' => 2, 'duration' => 3],
            ],
        ]);

        $response = $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/discounts-margin', [
            'sn_discount' => $snDiscount->getKey(),
            'promotional_discount' => $promotionalDiscount->getKey(),
            'multi_year_discount' => $multiYearDiscount->getKey(),
            'pre_pay_discount' => $prePayDiscount->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'total_price',
                'buy_price',
                'final_total_price',
                'applicable_discounts_value',
                'margin_after_pre_pay_discount',
                'margin_after_sn_discount',
                'margin_after_multi_year_discount',
                'margin_after_promotional_discount',
            ]);

        $this->assertEquals('3000.00', $response->json('total_price'));
        $this->assertEquals('2000.00', $response->json('buy_price'));
        $this->assertEquals('2542.37', $response->json('final_total_price'));
        $this->assertEquals('987.04', $response->json('applicable_discounts_value'));
        $this->assertEquals("41.33", $response->json('margin_after_multi_year_discount'));
        $this->assertEquals("38.33", $response->json('margin_after_pre_pay_discount'));
        $this->assertEquals("26.33", $response->json('margin_after_promotional_discount'));
        $this->assertEquals("21.33", $response->json('margin_after_sn_discount'));
    }

    /**
     * Test an ability to view price summary data after margin & tax.
     *
     * @return void
     */
    public function testCanViewPriceSummaryDataAfterMarginAndTax()
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

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 5]);

        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
            'sn_discount_id' => $snDiscount->getKey(),

        ]);

        $distributorQuote->vendors()->sync($vendor);

        $this->authenticateApi();

        $response = $this->postJson('api/ww-distributions/'.$distributorQuote->getKey().'/country-margin-tax-margin', [
            'margin_value' => 10,
            'tax_value' => 10,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'total_price',
                'buy_price',
                'final_total_price',
                'applicable_discounts_value',
                'margin_after_pre_pay_discount',
                'margin_after_sn_discount',
                'margin_after_multi_year_discount',
                'margin_after_promotional_discount',
                'final_margin',
            ]);

        $this->assertEquals('3000.00', $response->json('total_price'));
        $this->assertEquals('2000.00', $response->json('buy_price'));
        $this->assertEquals('3253.24', $response->json('final_total_price'));
        $this->assertEquals('3243.24', $response->json('final_total_price_excluding_tax'));
        $this->assertEquals('38.33', $response->json('final_margin'));
    }

    /**
     * Test an ability to view calculated distribution margin value after custom discount.
     *
     * @return void
     */
    public function testCanViewCalculatedDistributionMarginValueAfterCustomDiscount()
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

        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
            'margin_value' => 10,
        ]);

        $wwDistribution->vendors()->sync($vendor);

        $this->authenticateApi();

        $this->postJson('api/ww-distributions/'.$wwDistribution->getKey().'/custom-discount-margin', [
            'custom_discount' => 10.00,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'total_price',
                'buy_price',
                'final_total_price',
                'applicable_discounts_value',
                'margin_after_custom_discount',
                'margin_after_multi_year_discount',
                'margin_after_pre_pay_discount',
                'margin_after_promotional_discount',
                'margin_after_sn_discount',
            ])
            ->assertJson([
                'total_price' => '3000.00',
                'buy_price' => '2000.00',
                'final_total_price' => '3000.00',
                'applicable_discounts_value' => '529.41',
                'margin_after_custom_discount' => '33.33',
            ]);
    }


    /**
     * Test can apply predefined discounts to worldwide distributions.
     *
     * @return void
     */
    public function testCanApplyPredefinedDiscountsToWorldwideDistributions()
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
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistributions = factory(WorldwideDistribution::class, 10)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
        ]);

        $wwDistributions->each(function (WorldwideDistribution $worldwideDistribution) use ($vendor, $opportunity) {
            $worldwideDistribution->vendors()->sync($vendor);
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $worldwideDistribution->opportunitySupplier()->associate($supplier);
            $worldwideDistribution->save();
        });

        $this->authenticateApi();

        $snDiscount = factory(SND::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 5]);
        $promotionalDiscount = factory(PromotionalDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(), 'value' => 12, 'minimum_limit' => 1000]);
        $prePayDiscount = factory(PrePayDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
            'durations' => [
                'duration' => ['value' => 3, 'duration' => 3],
            ],
        ]);
        $multiYearDiscount = factory(MultiYearDiscount::class)->create(['vendor_id' => $vendor->getKey(), 'country_id' => $country->getKey(),
            'durations' => [
                'duration' => ['value' => 2, 'duration' => 3],
            ],
        ]);

        $discountsData = $wwDistributions->map(function (WorldwideDistribution $worldwideDistribution) use ($prePayDiscount, $multiYearDiscount, $promotionalDiscount, $snDiscount) {
            return [
                'id' => $worldwideDistribution->getKey(),
                'predefined_discounts' => [
                    'sn_discount' => $snDiscount->getKey(),
                    'promotional_discount' => $promotionalDiscount->getKey(),
                    'multi_year_discount' => $multiYearDiscount->getKey(),
                    'pre_pay_discount' => $prePayDiscount->getKey(),
                ],
            ];
        });

        $this->postJson('api/ww-distributions/discounts', ['worldwide_distributions' => $discountsData->all(), 'stage' => 'Discount'])->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.predefined_discounts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'stage',
                'worldwide_distributions' => [
                    '*' => [
                        'predefined_discounts' => [
                            'multi_year_discount' => ['id', 'name', 'durations' => ['duration' => ['value', 'duration']]],
                            'pre_pay_discount' => ['id', 'name', 'durations' => ['duration' => ['value', 'duration']]],
                            'promotional_discount' => ['id', 'name', 'minimum_limit', 'value'],
                            'sn_discount' => ['id', 'name', 'value'],
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('Discount', $response->json('stage'));
    }

    /**
     * Test can apply custom discounts to worldwide distributions.
     *
     * @return void
     */
    public function testCanApplyCustomDiscountsToWorldwideDistributions()
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
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistributions = factory(WorldwideDistribution::class, 10)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'country_id' => $country->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'buy_price' => $buyPrice = 2_000,
        ]);

        $wwDistributions->each(function (WorldwideDistribution $worldwideDistribution) use ($vendor, $opportunity) {
            $worldwideDistribution->vendors()->sync($vendor);

            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $worldwideDistribution->opportunitySupplier()->associate($supplier);
            $worldwideDistribution->save();
        });

        $this->authenticateApi();

        $discountsData = $wwDistributions->map(function (WorldwideDistribution $worldwideDistribution) {
            return [
                'id' => $worldwideDistribution->getKey(),
                'custom_discount' => mt_rand(0, 50),
            ];
        });

        $this->postJson('api/ww-distributions/discounts', ['worldwide_distributions' => $discountsData->all(), 'stage' => 'Discount'])->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions')
            ->assertJsonStructure([
                'stage',
                'worldwide_distributions' => [
                    '*' => [
                        'id', 'custom_discount',
                    ],
                ],
            ]);

        $this->assertEquals('Discount', $response->json('stage'));

        $distributionsResponse = collect($response->json('worldwide_distributions'))->pluck('custom_discount', 'replicated_distributor_quote_id');

        foreach ($discountsData as $distribution) {
            $this->assertEquals($distribution['custom_discount'], $distributionsResponse[$distribution['id']]);
        }
    }

    /**
     * Test can update details of worldwide distributions.
     *
     * @return void
     */
    public function testCanUpdateDetailsOfWorldwideDistributions()
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistributions = factory(WorldwideDistribution::class, 5)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass()]);

        $opportunity = Opportunity::factory()->create();

        $wwDistributions->each(function (WorldwideDistribution $distribution) use ($opportunity) {
            $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $distribution->opportunitySupplier()->associate($supplier);
            $distribution->save();
        });

        $postData = $wwDistributions->map(function (WorldwideDistribution $distribution) {
            return [
                'id' => $distribution->getKey(),
                'pricing_document' => $this->faker->text(),
                'service_agreement_id' => $this->faker->text(),
                'system_handle' => $this->faker->text(),
                'purchase_order_number' => $this->faker->text(),
                'vat_number' => $this->faker->text(),
                'additional_details' => $this->faker->text(),
            ];
        });

        $this->postJson('api/ww-distributions/details', ['worldwide_distributions' => $postData->all(), 'stage' => 'Additional Detail'])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions')->assertOk()
            ->assertJsonStructure([
                'stage',
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'replicated_distributor_quote_id',
                        'pricing_document',
                        'service_agreement_id',
                        'system_handle',
                        'purchase_order_number',
                        'vat_number',
                        'additional_details',
                    ],
                ],
            ]);

        $this->assertEquals('Additional Detail', $response->json('stage'));

        $postData = $postData->keyBy('id');

        foreach ($response->json('worldwide_distributions') as $distribution) {
            $this->assertArrayHasKey($distribution['replicated_distributor_quote_id'], $postData);

            $distributionPostData = $postData[$distribution['replicated_distributor_quote_id']];

            $this->assertEquals($distributionPostData['pricing_document'], $distribution['pricing_document']);
            $this->assertEquals($distributionPostData['service_agreement_id'], $distribution['service_agreement_id']);
            $this->assertEquals($distributionPostData['system_handle'], $distribution['system_handle']);
            $this->assertEquals($distributionPostData['purchase_order_number'], $distribution['purchase_order_number']);
            $this->assertEquals($distributionPostData['vat_number'], $distribution['vat_number']);
            $this->assertEquals($distributionPostData['additional_details'], $distribution['additional_details']);
        }
    }

    /**
     * Test an ability to delete a newly created worldwide distribution.
     *
     * @return void
     */
    public function testCanDeleteWorldwideDistribution()
    {
        $this->authenticateApi();


        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $wwDistribution = factory(WorldwideDistribution::class)->create(['opportunity_supplier_id' => $supplier->getKey()]);

        $this->deleteJson('api/ww-distributions/'.$wwDistribution->getKey())->assertNoContent();
    }


    /**
     * Test an ability to view computed distribution final total price, total_price, buy_price, margin_percentage.
     *
     * @return void
     */
    public function testCanViewComputedDistributionSummary()
    {
        $this->authenticateApi();

        /** @var WorldwideQuote $wwQuote */
        $wwQuote = factory(WorldwideQuote::class)->create();

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);

        $rows = factory(MappedRow::class)->create(['quote_file_id' => $distributorFile->getKey(), 'price' => 10_000, 'is_selected' => true]);

        /** @var WorldwideDistribution $wwDistribution */
        $wwDistribution = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
            'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
            'buy_price' => 10_000,
            'distributor_file_id' => $distributorFile->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.summary')->assertOk()
//            ->dump()
            ->assertJsonStructure([
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'margin_percentage_after_custom_discount',
                        'summary' => ['final_total_price', 'total_price', 'buy_price', 'margin_percentage'],
                    ],
                ],
            ]);

        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.total_price'));
        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.final_total_price'));
        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.buy_price'));
        $this->assertEquals(0, $response->json('worldwide_distributions.0.summary.margin_percentage'));
        $this->assertEquals(0, $response->json('worldwide_distributions.0.margin_percentage_after_custom_discount'));

        $wwDistribution->update(['buy_price' => 9_500, 'custom_discount' => 5]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.summary')->assertOk()
//            ->dump()
            ->assertJsonStructure([
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'margin_percentage_after_custom_discount',
                        'summary' => ['final_total_price', 'total_price', 'buy_price', 'margin_percentage'],
                    ],
                ],
            ]);

        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.total_price'));
        $this->assertEquals(9_500, $response->json('worldwide_distributions.0.summary.final_total_price'));
        $this->assertEquals(9_500, $response->json('worldwide_distributions.0.summary.buy_price'));
        $this->assertEquals(5, $response->json('worldwide_distributions.0.summary.margin_percentage'));
        $this->assertEquals(0.0, $response->json('worldwide_distributions.0.margin_percentage_after_custom_discount'));

        $wwDistribution->update(['custom_discount' => 0]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.summary')->assertOk()
            ->assertJsonStructure([
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'margin_percentage_after_custom_discount',
                        'summary' => ['final_total_price', 'total_price', 'buy_price', 'margin_percentage'],
                    ],
                ],
            ]);

        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.total_price'));
        $this->assertEquals(round(10_000, 2), $response->json('worldwide_distributions.0.summary.final_total_price'));
        $this->assertEquals(9_500, $response->json('worldwide_distributions.0.summary.buy_price'));
        $this->assertEquals(5, $response->json('worldwide_distributions.0.summary.margin_percentage'));
        $this->assertEquals(5, $response->json('worldwide_distributions.0.margin_percentage_after_custom_discount'));

        $wwDistribution->update(['custom_discount' => null]);
        $wwDistribution->multiYearDiscount()->associate(
            factory(MultiYearDiscount::class)->create(['durations' => ['duration' => ['value' => 5, 'duration' => 3]]])
        );
        $wwDistribution->save();

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'?include[]=worldwide_distributions.summary&include[]=worldwide_distributions.predefined_discounts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'worldwide_distributions' => [
                    '*' => [
                        'id',
                        'margin_percentage_after_custom_discount',
                        'summary' => ['final_total_price', 'total_price', 'buy_price', 'margin_percentage'],
                        'predefined_discounts' => [
                            'multi_year_discount' => ['id', 'name', 'durations' => ['duration' => ['value', 'duration']]],
                        ],
                    ],
                ],
            ]);

        $this->assertEquals(0, $response->json('worldwide_distributions.0.predefined_discounts.margin_percentage'));

        $this->assertEquals(10_000, $response->json('worldwide_distributions.0.summary.total_price'));
        $this->assertEquals(9_500, $response->json('worldwide_distributions.0.summary.final_total_price'));
        $this->assertEquals(9_500, $response->json('worldwide_distributions.0.summary.buy_price'));
        $this->assertEquals(5, $response->json('worldwide_distributions.0.summary.margin_percentage'));
        $this->assertEquals(5, $response->json('worldwide_distributions.0.margin_percentage_after_custom_discount'));


    }

    /**
     * Test an ability to update a row of an existing worldwide distributor quote.
     *
     * @return void
     */
    public function testCanUpdateRowOfWorldwideDistributorQuote()
    {
        $this->authenticateApi();

        $quote = factory(WorldwideQuote::class)->create([
            'user_id' => $this->app['auth']->id(),
        ]);

        $quote->activeVersion->update(['user_id' => $this->app['auth']->id()]);

        $opportunity = Opportunity::factory()->create();
        $supplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);

        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => QFT_WWPL,
        ]);

        /** @var MappedRow $row */
        $row = factory(MappedRow::class)->create(['quote_file_id' => $distributorFile->getKey(), 'price' => 10_000, 'is_selected' => true]);

        $machineAddress = factory(Address::class)->create();

        /** @var WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([

            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'buy_price' => 10_000,
            'distributor_file_id' => $distributorFile->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
        ]);

        $rowFieldsData = [
            'product_no' => 'ABCDF-0000',
            'description' => null,
            'date_from' => '2021-01-22',
            'date_to' => '2022-01-24',
            'price' => '120000.10',
            'original_price' => 120000.10 * .9,
            'machine_address_id' => $machineAddress->getKey(),
        ];

        /** @var Asset $sameAsset */
        $sameAsset = factory(Asset::class)->create([
            'product_number' => $rowFieldsData['product_no'],
            'serial_number' => $row->serial_no,
        ]);

        $sameAsset->companies()->sync(Company::factory()->create());

        $this->patchJson('api/ww-distributions/'.$distributorQuote->getKey().'/mapped-rows/'.$row->getKey(), $rowFieldsData)
//            ->dump()
            ->assertOk()
            ->assertJson($rowFieldsData)
            ->assertJsonStructure([
                'is_customer_exclusive_asset',
                'owned_by_customer',
            ]);

        $response = $this->getJson('api/ww-distributions/'.$distributorQuote->getKey().'/mapped-rows/'.$row->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'machine_address_id',
                'product_no',
                'description',
                'date_from',
                'date_to',
                'price',
                'original_price',
                'machine_address_id',
            ]);


        foreach ($rowFieldsData as $field => $value) {
            $this->assertEquals($value, $response->json($field));
        }
    }

    /**
     * Test worldwide quote distribution initialization.
     *
     * @return void
     */
    public function testWorldwideQuoteDistributionInit()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create(['user_id' => auth()->id()]);

        $this->postJson('api/ww-distributions', [
            'worldwide_quote_id' => $wwQuote->getKey(),
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'worldwide_quote_id',
                'created_at',
                'updated_at',
            ]);
    }
}
