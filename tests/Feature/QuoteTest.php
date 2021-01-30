<?php

namespace Tests\Feature;

use App\DTO\RowsGroup;
use App\Models\{Data\Currency,
    Quote\Discount,
    Quote\Discount\MultiYearDiscount,
    QuoteFile\ImportableColumn,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile,
    QuoteFile\ScheduleData,
    Template\TemplateField};
use App\Models\Quote\Quote;
use Illuminate\Support\{Arr, Str};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Unit\Traits\{WithFakeQuote, WithFakeQuoteFile, WithFakeUser,};

/**
 * @group build
 */
class QuoteTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, WithFakeQuoteFile;


    /**
     * Test an ability to view paginated drafted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedDraftedQuotes()
    {
        $this->get('api/quotes/drafted')->assertOk();
    }

    /**
     * Test an ability to view paginated submitted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedSubmittedQuotes()
    {
        $this->get('api/quotes/submitted')->assertOk();
    }

    /**
     * Test an ability to update a submitted quote.
     *
     * @return void
     */
    public function testCanNotUpdateSubmittedQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->submit();

        $state = ['quote_id' => $quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => QSU_01,
            ]);
    }

    /**
     * Test an ability to update a drafted quote.
     *
     * @return void
     */
    public function testCanUpdateDraftedQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->unSubmit();

        $state = ['quote_id' => $quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertOk()
            ->assertExactJson(['id' => $quote->id]);
    }

    /**
     * Test an ability to unravel an existing submitted quote.
     *
     * @return void
     */
    public function testCanUnravelQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->forceFill(['submitted_at' => now()])->save();

        $this->putJson(url("api/quotes/submitted/unsubmit/{$quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($quote->refresh()->submitted_at);
    }

    /**
     * Test an ability to copy an existing submitted quote.
     *
     * @return void
     */
    public function testCanCopySubmittedQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->forceFill(['submitted_at' => now()])->save();

        $this->putJson(url("api/quotes/submitted/copy/{$quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }


    /**
     * Test an ability to create a new group description of a quote.
     *
     * @return void
     */
    public function testCanCreateGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $attributes = $this->generateGroupDescription($quoteFile);

        $this->postJson(url("api/quotes/groups/{$quote->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text',
            ]);
        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'group_description' => [
                    '*' => [
                        'id',
                        'name',
                        'search_text',
                        'is_selected',
                        'rows_ids',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('group_description'));
    }

    /**
     * Test an ability to update an existing group description of a quote.
     *
     * @return void
     */
    public function testCanUpdateGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $attributes = $this->generateGroupDescription($quoteFile);

        $this->patchJson("api/quotes/groups/".$quote->getKey().'/'.$group->id, $attributes)
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'group_description' => [
                    '*' => [
                        'id',
                        'name',
                        'search_text',
                        'is_selected',
                        'rows_ids',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('group_description'));
        $this->assertEquals($attributes['name'], $response->json('group_description.0.name'));
        $this->assertEquals($attributes['search_text'], $response->json('group_description.0.search_text'));
        $this->assertCount(count($attributes['rows']), $response->json('group_description.0.rows_ids'));
    }

    /**
     * Test can delete an existing group description of a quote.
     *
     * @return void
     */
    public function testCanDeleteGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $this->deleteJson("api/quotes/groups/".$quote->getKey().'/'.$group->id)
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'group_description' => [
                    '*' => [
                        'id',
                        'name',
                        'search_text',
                        'is_selected',
                        'rows_ids',
                    ],
                ],
            ]);

        $this->assertEmpty($response->json('group_description'));
    }

    /**
     * Test an ability to move rows of an existing group description to an another group description.
     *
     * @return void
     */
    public function testCanMoveRowsOfGroupDescriptionToAnotherGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $groups = collect()->times(2)->transform(fn() => $this->createFakeGroupDescription($quote, $quoteFile));

        /** @var RowsGroup $fromGroup */
        $fromGroup = $groups->shift();

        /** @var RowsGroup $toGroup */
        $toGroup = $groups->shift();

        $attributes = [
            'from_group_id' => $fromGroup->id,
            'to_group_id' => $toGroup->id,
            'rows' => $fromGroup->rows_ids,
        ];

        $this->putJson("api/quotes/groups/".$quote->getKey(), $attributes)
            ->assertOk();

        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'group_description' => [
                    '*' => [
                        'id',
                        'name',
                        'search_text',
                        'is_selected',
                        'rows_ids',
                    ],
                ],
            ]);

        $groupRowsPairs = Arr::pluck($response->json('group_description'), 'rows_ids', 'id');

        $this->assertEmpty($groupRowsPairs[$fromGroup->id]);
        $this->assertCount(count($attributes['rows']) + count($toGroup->rows_ids), $groupRowsPairs[$toGroup->id]);
    }

    /**
     * Test an ability to view an existing group description of a quote.
     *
     * @return void
     */
    public function testCanViewGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $this->getJson(url("api/quotes/groups/{$quote->id}/{$group->id}"))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text', 'total_count', 'total_price', 'rows',
            ]);
    }

    /**
     * Test an ability to view listing of group description of a quote.
     *
     * @return void
     */
    public function testCanViewListingOfGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $count = 10;

        collect()->times($count)->each(fn() => $this->createFakeGroupDescription($quote, $quoteFile));

        $response = $this->getJson("api/quotes/groups/".$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'search_text',
                    'total_count',
                    'total_price',
                    'rows',
                ],
            ]);

        $this->assertCount($count, $response->json());
    }

    /**
     * Test an ability to mark as selected specified group description of a quote.
     *
     * @return void
     */
    public function testCanSelectGroupDescription()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $count = 10;

        $groups = collect()->times($count)->map(fn($group) => $this->createFakeGroupDescription($quote, $quoteFile));

        $selected = $groups->random(mt_rand(1, $count))->keyBy('id');

        $this->putJson(url("api/quotes/groups/{$quote->id}/select"), $selected->pluck('id')->toArray())
            ->assertOk();

        $groups = Collection::wrap($quote->refresh()->group_description);

        /**
         * Assert that groups actual is_selected attribute is matching to selected groups.
         */
        $groups->each(fn(RowsGroup $group) => $this->assertEquals($selected->has($group->id), $group->is_selected));

        /**
         * Assert that quote review endpoint is displaying selected groups.
         */
        $quote->update(['use_groups' => true]);

        $response = $this->get(url("api/quotes/review/{$quote->id}"));

        $reviewData = $response->json();

        $reviewGroupsIds = data_get($reviewData, 'data_pages.rows.*.id');

        $selected->each(fn(RowsGroup $group) => $this->assertTrue(in_array($group->id, $reviewGroupsIds)));
    }

    /**
     * Test an ability to export an existing submitted quote.
     *
     * @return void
     */
    public function testCanExportSubmittedQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->submit();

        $response = $this->getJson(url("api/quotes/submitted/pdf/{$quote->id}"));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test an ability to download quote distributor file.
     *
     * @return void
     */
    public function testCanDownloadQuoteDistributorFile()
    {
        $quote = $this->createQuote($this->user);

        Storage::fake();

        $filePath = $this->user->quote_files_directory.'/'.Str::random(40).'.pdf';

        Storage::put($filePath, file_get_contents(base_path('tests/Unit/Data/distributor-files-test/HPInvent1547101.pdf')));

        $distributorFile = QuoteFile::create([
            'quote_file_format_id' => DB::table('quote_file_formats')->where('extension', 'pdf')->value('id'),
            'original_file_name' => 'HPInvent1547101.pdf',
            'original_file_path' => $filePath,
            'file_type' => QFT_PL,
        ]);

        $quote->priceList()->associate($distributorFile)->save();

        $this->get("api/quotes/get/$quote->id/quote-files/price")
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=HPInvent1547101.pdf')
            ->assertHeader('content-type', 'application/pdf');
    }


    /**
     * Test an ability to download quote payment schedule file.
     *
     * @return void
     */
    public function testCanDownloadQuoteScheduleFile()
    {
        $quote = $this->createQuote($this->user);

        Storage::fake();

        $filePath = $this->user->quote_files_directory.'/'.Str::random(40).'.pdf';

        Storage::put($filePath, file_get_contents(base_path('tests/Unit/Data/distributor-files-test/HPInvent1547101.pdf')));

        $distributorFile = QuoteFile::create([
            'quote_file_format_id' => DB::table('quote_file_formats')->where('extension', 'pdf')->value('id'),
            'original_file_name' => 'HPInvent1547101.pdf',
            'original_file_path' => $filePath,
            'file_type' => QFT_PS,
        ]);

        $quote->paymentSchedule()->associate($distributorFile)->save();

        $this->get("api/quotes/get/$quote->id/quote-files/schedule")
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=HPInvent1547101.pdf')
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test an ability to view mapping with enabled default value of template fields.
     *
     * @return void
     */
    public function testCanViewMappingWithEnabledDefaultValueOfTemplateFields()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $rows = factory(ImportedRow::class, 200)->create(['quote_file_id' => $quoteFile->id]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $defaults = ['date_from' => true, 'date_to' => true];

        $map = $templateFields->flip()->map(fn($name, $id) => ['importable_column_id' => $importableColumns->get($name), 'is_default_enabled' => Arr::get($defaults, $name, false)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/'.$quote->id)->assertOk();

        $this->getJson('api/quotes/review/'.$quote->id)->assertOk();
    }

    /**
     * Test can view mapping with completely mapped template fields.
     *
     * @return void
     */
    public function testCanViewMappingWithCompletelyMappedTemplateFields()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $rows = factory(ImportedRow::class, 20)->create(['quote_file_id' => $quoteFile->id, 'is_selected' => true]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $map = $templateFields->flip()->map(fn($name, $id) => ['importable_column_id' => $importableColumns->get($name)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/'.$quote->id)->assertOk();

        $this->getJson('api/quotes/review/'.$quote->id)
            ->assertOk()
            ->assertJsonStructure([
                'first_page',
                'last_page',
                'data_pages' => [
                    'rows_header' => ['product_no', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'searchable'],
                    'rows' => [
                        '*' => ['id', 'is_selected', 'product_no', 'serial_no', 'description', 'date_from', 'date_to', 'qty', 'price', 'searchable'],
                    ],
                ],
                'payment_schedule',
            ]);
    }

    /**
     * Test an ability to view Quote State data.
     *
     * @return void
     */
    public function testCanViewQuoteState()
    {
        /** @var Quote $quote */
        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => 'Distributor Price List',
            'imported_page' => 2,
        ]);

        $importedRows = factory(ImportedRow::class, 2)->make([
            'quote_file_id' => $distributorFile->getKey(),
            'page' => 2,
            'is_selected' => true,
        ]);

        $templateFields = TemplateField::query()->where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::query()->where('is_system', true)->pluck('id', 'name');

        $priceColumnId = $templateFields['price'];

        $importedRows->each(function (ImportedRow $row) use ($templateFields, $priceColumnId) {
            $row->columns_data[$priceColumnId] = array_merge($row->columns_data[$priceColumnId], ['value' => "100.00"]);

            $row->save();
        });

        $scheduleFile = factory(QuoteFile::class)->create([
            'file_type' => 'Payment Schedule',
            'imported_page' => 2,
        ]);

        with($scheduleFile, function (QuoteFile $file) {

            $scheduleData = new ScheduleData([
                'quote_file_id' => $file->getKey(),
                'value' => [
                    [
                        'from' => '01.01.2020',
                        'to' => '30.01.2020',
                        'price' => '80,00',
                    ],
                    [
                        'from' => '31.01.2020',
                        'to' => '30.01.2021',
                        'price' => '120,00',
                    ],
                ],
            ]);

            $scheduleData->save();

        });

        $quote = factory(Quote::class)->create([
            'distributor_file_id' => $distributorFile->getKey(),
            'schedule_file_id' => $scheduleFile->getKey(),
            'completeness' => 99,
            'buy_price' => 120,
            'calculate_list_price' => false,
            'country_margin_id' => null,
            'custom_discount' => 10,
            'source_currency_id' => Currency::where('code', 'GBP')->value('id'),
            'target_currency_id' => null,
            'exchange_rate_margin' => 7,
        ]);

        $mapping = [];

        foreach ($templateFields as $name => $id) {
            $mapping[$id] = ['importable_column_id' => $importableColumns[$name] ?? null];
        }

        $quote->templateFields()->sync($mapping);


        $response = $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'quote_template_id',
                'contract_template_id',
                'company_id',
                'vendor_id',
                'customer_id',
                'country_id',
                'country_margin_id',
                'source_currency_id',
                'target_currency_id',
                'exchange_rate_margin',
                'actual_exchange_rate',
                'target_exchange_rate',
                'type',
                'previous_state',
                'completeness',
                'last_drafted_step',
                'margin_data' => [
                    'quote_type',
                ],
                'pricing_document',
                'service_agreement_id',
                'system_handle',
                'additional_details',
                'checkbox_status',
                'closing_date',
                'additional_notes',
                'list_price',
                'calculate_list_price',
                'buy_price',
                'group_description',
                'use_groups',
                'sort_group_description',
                'has_group_description',
                'version_number',
                'hidden_fields',
                'sort_fields',
                'field_column' => [
                    '*' => [
                        'template_field_id',
                        'template_field_name',
                        'importable_column_id',
                        'is_default_enabled',
                        'is_preview_visible',
                        'sort',
                        'type',
                    ],
                ],
                'rows_data' => [
                    '*' => [
                        'id',
                        'columns_data' => [
                            '*' => [
                                'value',
                                'header',
                                'importable_column_id',
                            ],
                        ],
                    ],
                ],
                'margin_percentage_without_country_margin',
                'margin_percentage_without_discounts',
                'user_margin_percentage',
                'custom_discount',
                'quote_files' => [
                    '*' => [
                        'id',
                        'user_id',
                        'file_type',
                        'original_file_path',
                        'original_file_name',
                        'pages',
                        'imported_page',
                        'meta_attributes',
                        'created_at',
                        'updated_at',
                        'automapped_at',
                        'quote_file_format_id',
                        'data_select_separator_id',
                        'handled_at',
                    ],
                ],
                'quote_template' => [
                    'id',
                    'template_fields' => [
                        '*' => [
                            'id',
                            'header',
                            'name',
                            'order',
                            'default_value',
                            'is_required',
                            'is_column',
                            'type',
                        ],
                    ],
                    'currency' => [
                        'id', 'name', 'code', 'symbol',
                    ],
                    'form_data',
                    'data_headers' => [
                        'product_no' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'description' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'serial_no' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'date_from' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'date_to' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'qty' => [
                            'key',
                            'label',
                            'value',
                        ],
                        'price' => [
                            'key',
                            'label',
                            'value',
                        ],
                    ],
                ],

                'country_margin',
                'discounts',
                'belongs_to_eq_customer',
                'customer' => [
                    'id',
                    'name',
                    'rfq',
                    'valid_until',
                    'valid_until_ui',
                    'support_start',
                    'support_end',
                    'payment_terms',
                    'invoicing_terms',
                    'service_levels',
                    'source',
                ],
                'country' => [
                    'id',
                    'default_currency_id',
                    'iso_3166_2',
                    'name',
                    'currency_name',
                    'currency_symbol',
                    'currency_code',
                    'flag',
                ],
                'vendor' => [
                    'id',
                    'name',
                    'short_code',
                    'logo',
                ],
                'fields_columns',
                'created_at',
                'submitted_at',

            ]);

        $this->assertCount(2, $response->json('quote_files'));

        $this->assertEquals('10.00', $response->json('custom_discount'));
        $this->assertEquals(40, $response->json('user_margin_percentage'));
        $this->assertEquals(7, $response->json('exchange_rate_margin'));
    }

    /**
     * Test an ability to view Quote Preview data.
     *
     * @return void
     */
    public function testCanViewQuotePreviewData()
    {
        /** @var Quote $quote */
        $distributorFile = factory(QuoteFile::class)->create([
            'file_type' => 'Distributor Price List',
            'imported_page' => 2,
        ]);

        $importedRows = factory(ImportedRow::class, 2)->make([
            'quote_file_id' => $distributorFile->getKey(),
            'page' => 2,
            'is_selected' => true,
        ]);

        $templateFields = TemplateField::query()->where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::query()->where('is_system', true)->pluck('id', 'name');

        $priceColumnId = $templateFields['price'];

        $importedRows->each(function (ImportedRow $row) use ($templateFields, $priceColumnId) {
            $row->columns_data[$priceColumnId] = array_merge($row->columns_data[$priceColumnId], ['value' => "100.00"]);

            $row->save();
        });

        $scheduleFile = factory(QuoteFile::class)->create([
            'file_type' => 'Payment Schedule',
            'imported_page' => 2,
        ]);

        with($scheduleFile, function (QuoteFile $file) {

            $scheduleData = new ScheduleData([
                'quote_file_id' => $file->getKey(),
                'value' => [
                    [
                        'from' => '01.01.2020',
                        'to' => '30.01.2020',
                        'price' => '80,00',
                    ],
                    [
                        'from' => '31.01.2020',
                        'to' => '30.01.2021',
                        'price' => '120,00',
                    ],
                ],
            ]);

            $scheduleData->save();

        });

        $quote = factory(Quote::class)->create([
            'distributor_file_id' => $distributorFile->getKey(),
            'schedule_file_id' => $scheduleFile->getKey(),
            'completeness' => 99,
            'buy_price' => 200,
            'calculate_list_price' => false,
            'country_margin_id' => null,
            'custom_discount' => 10,
            'source_currency_id' => Currency::where('code', 'GBP')->value('id'),
            'target_currency_id' => null,
        ]);

        $mapping = [];

        foreach ($templateFields as $name => $id) {
            $mapping[$id] = ['importable_column_id' => $importableColumns[$name] ?? null];
        }

        $quote->templateFields()->sync($mapping);

        $response = $this->getJson('api/quotes/review/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'first_page' => [
                    'template_name',
                    'customer_name',
                    'company_name',
                    'company_logo',
                    'vendor_name',
                    'vendor_logo',
                    'support_start',
                    'support_end',
                    'valid_until',
                    'quotation_number',
                    'service_levels',
                    'list_price', // £ 200.00
                    'applicable_discounts', // £ 18.18
                    'final_price', // £ 181.82
                    'invoicing_terms',
                    'full_name',
                    'date',
                    'service_agreement_id',
                    'system_handle',
                ],
                'data_pages' => [
                    'pricing_document',
                    'service_agreement_id',
                    'system_handle',
                    'service_levels',
                    'equipment_address',
                    'hardware_contact',
                    'hardware_phone',
                    'software_address',
                    'software_contact',
                    'software_phone',
                    'additional_details',
                    'coverage_period',
                    'coverage_period_from',
                    'coverage_period_to',
                    'rows_header' => [
                        'product_no',
                        'description',
                        'serial_no',
                        'date_from',
                        'date_to',
                        'qty',
                        'price',
                        'searchable',
                    ],
                    'rows' => [
                        '*' => [
                            'id',
                            'is_selected',
                            'product_no',
                            'serial_no',
                            'description',
                            'date_from',
                            'date_to',
                            'qty',
                            'price',
                            'searchable',
                        ],
                    ],
                ],
                'last_page' => [
                    'additional_details',
                ],
                'payment_schedule' => [
                    'company_name',
                    'vendor_name',
                    'customer_name',
                    'support_start',
                    'support_end',
                    'period',
                    'rows_header' => [
                        'from',
                        'to',
                        'price',
                    ],
                    'total_payments',
                    'data' => [
                        '*' => [
                            'from',
                            'to',
                            'price',
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('£ 200.00', $response->json('first_page.list_price'));
        $this->assertEquals('£ 18.18', $response->json('first_page.applicable_discounts'));
        $this->assertEquals('£ 181.82', $response->json('first_page.final_price'));

        $this->assertCount(2, $response->json('data_pages.rows'));
        $this->assertEquals('£ 90.91', $response->json('data_pages.rows.0.price'));
        $this->assertEquals('£ 90.91', $response->json('data_pages.rows.1.price'));

        $this->assertCount(2, $response->json('payment_schedule.data'));
        $this->assertEquals('£ 72.73', $response->json('payment_schedule.data.0.price'));
        $this->assertEquals('£ 109.09', $response->json('payment_schedule.data.1.price'));


        $quote->custom_discount = null;
        $quote->save();

        $multiYearDiscount = factory(MultiYearDiscount::class)->create([
            'durations' => [
                'duration' => [
                    'duration' => 1, 'value' => 10,
                ],
            ],
        ]);

        $polymorphMYDiscount = Discount::where('discountable_id', $multiYearDiscount->getKey())->first();

        $quote->discounts()->sync($polymorphMYDiscount);

        $response = $this->getJson('api/quotes/review/'.$quote->getKey())
            ->assertOk();

        $this->assertEquals('£ 200.00', $response->json('first_page.list_price'));
        $this->assertEquals('£ 20.00', $response->json('first_page.applicable_discounts'));
        $this->assertEquals('£ 180.00', $response->json('first_page.final_price'));

        $this->assertCount(2, $response->json('data_pages.rows'));
        $this->assertEquals('£ 90.00', $response->json('data_pages.rows.0.price'));
        $this->assertEquals('£ 90.00', $response->json('data_pages.rows.1.price'));

        $this->assertCount(2, $response->json('payment_schedule.data'));
        $this->assertEquals('£ 72.00', $response->json('payment_schedule.data.0.price'));
        $this->assertEquals('£ 108.00', $response->json('payment_schedule.data.1.price'));
    }

    protected function createFakeGroupDescription(Quote $quote, QuoteFile $quoteFile): RowsGroup
    {
        $attributes = $this->generateGroupDescription($quoteFile);
        /** @var \App\DTO\RowsGroup */
        $group = $this->quoteRepository->createGroupDescription($attributes, $quote);

        return tap($group, fn() => $group->rows_ids = $attributes['rows']);
    }

    protected function generateGroupDescription(QuoteFile $quoteFile): array
    {
        $group_name = $this->faker->unique()->sentence(3);

        $rows = factory(ImportedRow::class, 4)->create(['quote_file_id' => $quoteFile->id]);

        return [
            'name' => $group_name,
            'search_text' => $this->faker->sentence(3),
            'rows' => $rows->pluck('id')->toArray(),
        ];
    }
}
