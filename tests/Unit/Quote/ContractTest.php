<?php

namespace Tests\Unit\Quote;

use App\DTO\RowsGroup;
use App\Models\Quote\Contract;
use App\Models\Quote\Quote;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Models\QuoteFile\ScheduleData;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing, WithFakeUser};

/**
 * @group build
 */
class ContractTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    use DatabaseTransactions;

    protected static $assertableAttributes = [
        'id',
        'quote_id',
        'user_id',
        'quote_template_id',
        'contract_template_id',
        'company_id',
        'vendor_id',
        'customer_id',
        'country_margin_id',
        'source_currency_id',
        'target_currency_id',
        'exchange_rate_margin',
        'actual_exchange_rate',
        'target_exchange_rate',
        'type',
        'completeness',
        'last_drafted_step',
        'margin_data',
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
        'field_column',
        'rows_data',
        'margin_percentage_without_country_margin',
        'margin_percentage_without_discounts',
        'user_margin_percentage',
        'custom_discount',
        'quote_files',
        'contract_template',
        'country_margin',
        'discounts',
        'customer',
        'country',
        'vendor',
        'company',
        'template_fields',
        'fields_columns',
        'versions_selection',
        'created_at',
    ];

    /**
     * Test contract listing.
     *
     * @return void
     */
    public function testContractListing()
    {
        $this->assertListing($this->getJson('api/contracts/drafted'));
        $this->assertListing($this->getJson('api/contracts/submitted'));
    }

    /**
     * Test contract creating based on submitted quote.
     *
     * @return void
     */
    public function testContractCreatingBasedOnSubmittedQuote()
    {
        $quote = tap(factory(Quote::class)->create())->submit();

        $contractTemplate = factory(ContractTemplate::class)->create();

        $this->postJson(url('api/quotes/submitted/contract/'.$quote->getKey()), ['contract_template_id' => $contractTemplate->getKey()])
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test contract creating based on submitted quote with group description.
     *
     * @return void
     */
    public function testContractCreatingBasedOnSubmittedQuoteWithGroupDescription()
    {
        /** @var Quote */
        $quote = tap(factory(Quote::class)->create())->submit();

        $priceList = $quote->priceList()->create([
            'original_file_path' => Str::random(),
            'original_file_name' => Str::random(),
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'data_select_separator_id' => DataSelectSeparator::value('id'),
            'imported_page' => 1,
        ]);

        $quote->priceList()->associate($priceList)->save();

        $priceList->rowsData()->createMany([
            [
                'page' => 1,
                'columns_data' => [],
            ],
            [
                'page' => 1,
                'columns_data' => [],
            ],
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection */
        $rows = $priceList->rowsData;

        tap($quote, function (Quote $quote) use ($rows) {
            $groups = collect([
                new RowsGroup([
                    'id' => (string)Str::uuid(),
                    'name' => 'Group',
                    'search_text' => '1234',
                    'is_selected' => true,
                    'rows_ids' => $rows->modelKeys(),
                ]),
            ]);

            $quote->use_groups = true;

            $quote->group_description = $groups;

            $quote->save();
        });

        $contractTemplate = factory(ContractTemplate::class)->create();

        $response = $this->postJson(url('api/quotes/submitted/contract/'.$quote->getKey()), ['contract_template_id' => $contractTemplate->getKey()])
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);

        $contract = Contract::find($response->json('id'));

        $this->assertInstanceOf(Contract::class, $contract);

        /** @var \Illuminate\Support\Collection */
        $contractRows = $contract->rowsData()->pluck('imported_rows.id', 'imported_rows.replicated_row_id');

        /** @var \Illuminate\Support\Collection */
        $contractGroups = $contract->group_description;

        $contractGroupedRows = $contractGroups->pluck('rows_ids')->collapse();

        $rows->pluck('id')->each(function ($key) use ($contractGroupedRows, $contractRows) {
            $this->assertTrue($contractRows->has($key));

            $this->assertNotSame($contractRows->get($key), $key);

            $this->assertContains($contractRows->get($key), $contractGroupedRows);
        });
    }

    /**
     * Test displaying a newly created Contract.
     *
     * @return void
     */
    public function testContractDisplaying()
    {
        $contract = $this->createFakeContract();

        $this->getJson(url('api/contracts/state/'.$contract->id))
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test an ability to review an existing contract.
     *
     * @return void
     */
    public function testCanReviewContract()
    {
        /** @var QuoteFile $priceList */
        $priceList = factory(QuoteFile::class)->state('rescue-price-list')->create();

        $importedRows = factory(ImportedRow::class, 2)->create([
            'quote_file_id' => $priceList->getKey(),
        ]);

        /** @var QuoteFile $paymentSchedule */
        $paymentSchedule = factory(QuoteFile::class)->state('rescue-payment-schedule')->create();

        /** @var ScheduleData $scheduleData */
        $scheduleData = factory(ScheduleData::class)->create([
            'quote_file_id' => $paymentSchedule->getKey(),
        ]);

        /** @var Contract $contract */
        $contract = factory(Contract::class)->create([
            'distributor_file_id' => $priceList->getKey(),
            'schedule_file_id' => $paymentSchedule->getKey(),
            'group_description' => collect([
                new RowsGroup([
                    'id' => (string)Str::uuid(),
                    'name' => Str::random(),
                    'search_text' => Str::random(),
                    'rows_ids' => $importedRows->modelKeys(),
                ]),
            ]),
            'use_groups' => true,
        ]);

        $this->getJson('api/contracts/state/review/'.$contract->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure(['first_page', 'data_pages', 'last_page', 'payment_schedule']);
    }

    /**
     * Test an ability to export an existing contract to pdf.
     *
     * @return void
     */
    public function testCanExportContract()
    {
        /** @var QuoteFile $priceList */
        $priceList = factory(QuoteFile::class)->state('rescue-price-list')->create();

        $importedRows = factory(ImportedRow::class, 2)->create([
            'quote_file_id' => $priceList->getKey(),
            'is_selected' => true,
        ]);

        /** @var QuoteFile $paymentSchedule */
        $paymentSchedule = factory(QuoteFile::class)->state('rescue-payment-schedule')->create();

        /** @var ScheduleData $scheduleData */
        $scheduleData = factory(ScheduleData::class)->create([
            'quote_file_id' => $paymentSchedule->getKey(),
        ]);

        $contractTemplate = factory(ContractTemplate::class)->create();

        /** @var Contract $contract */
        $contract = factory(Contract::class)->create([
            'distributor_file_id' => $priceList->getKey(),
            'schedule_file_id' => $paymentSchedule->getKey(),
            'group_description' => collect([
                new RowsGroup([
                    'id' => (string)Str::uuid(),
                    'name' => Str::random(),
                    'search_text' => Str::random(),
                    'rows_ids' => $importedRows->modelKeys(),
                ]),
            ]),
            'use_groups' => true,
            'contract_template_id' => $contractTemplate->getKey(),
        ]);

        $contract->quote->activeVersionOrCurrent->contractTemplate()->associate($contractTemplate);
        $contract->quote->activeVersionOrCurrent->group_description = $contract->group_description;
        $contract->quote->activeVersionOrCurrent->priceList()->associate($priceList);
        $contract->quote->activeVersionOrCurrent->paymentSchedule()->associate($paymentSchedule);
        $contract->quote->activeVersionOrCurrent->use_groups = true;
        $contract->quote->activeVersionOrCurrent->save();

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $map = $templateFields->flip()->map(fn($name, $id) => ['importable_column_id' => $importableColumns->get($name)]);

        $contract->quote->activeVersionOrCurrent->templateFields()->sync($map->all());

        $this->getJson('api/quotes/submitted/pdf/'.$contract->quote->getKey().'/contract')
//            ->dump()
            ->assertOk();
    }

    /**
     * Test updating a newly created Contract.
     *
     * @return void
     */
    public function testContractUpdating()
    {
        $contract = $this->createFakeContract();

        $attributes = ['additional_notes' => $this->faker->text];

        $this->patchJson(url('api/contracts/state/'.$contract->id), $attributes)
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test submitting a newly created Contract.
     *
     * @return void
     */
    public function testContractSubmitting()
    {
        $contract = $this->createFakeContract();

        $this->postJson(url('api/contracts/drafted/submit/'.$contract->id))
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($contract->refresh()->submitted_at);
    }

    /**
     * Test undo a newly created Contract.
     *
     * @return void
     */
    public function testContractUnravel()
    {
        $contract = tap($this->createFakeContract())->submit();

        $this->postJson(url('api/contracts/submitted/unsubmit/'.$contract->id))
            ->assertOk()->assertExactJson([true]);

        $this->assertNull($contract->refresh()->submitted_at);
    }

    /**
     * Test deleting a newly created submitted Contract.
     *
     * @return void
     */
    public function testSubmittedContractDeleting()
    {
        $contract = tap($this->createFakeContract())->submit();

        $this->deleteJson(url('api/contracts/submitted/'.$contract->id))
            ->assertOk()->assertExactJson([true]);

        $this->assertSoftDeleted($contract);
    }

    /**
     * Test deleting a newly created drafted Contract.
     *
     * @return void
     */
    public function testDraftedContractDeleting()
    {
        $contract = tap($this->createFakeContract())->unsubmit();

        $this->deleteJson(url('api/contracts/drafted/'.$contract->id))
            ->assertOk()->assertExactJson([true]);

        $this->assertSoftDeleted($contract);
    }

    /**
     * Test download the contract PDF having the respective permissions.
     *
     * @return void
     */
    public function testSubmittedContractDownload()
    {
        $contract = tap($this->createFakeContract())->submit();

        $this->assertTrue($this->user->can('download_contract_pdf'));

        $this->get(url('api/quotes/submitted/pdf/'.$contract->quote_id.'/contract'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test download the contract PDF not having the respective permissions.
     *
     * @return void
     */
    public function testSubmittedContractDownloadWithoutPermissions()
    {
        $this->user->role->revokePermissionTo('download_contract_pdf');

        $contract = tap($this->createFakeContract())->submit();

        $this->assertFalse($this->user->can('download_contract_pdf'));

        $this->get(url('api/quotes/submitted/pdf/'.$contract->quote_id.'/contract'))
            ->assertForbidden();

        $this->user->role->givePermissionTo('download_contract_pdf');
    }

    protected function createFakeContract(): Contract
    {
        /** @var \App\Models\Template\QuoteTemplate */
        $quoteTemplate = factory(QuoteTemplate::class)->create();

        /** @var \App\Models\Template\ContractTemplate */
        $contractTemplate = factory(ContractTemplate::class)->create();

        $attributes = ['contract_template_id' => $contractTemplate->getKey()];

        return app('contract.state')->createFromQuote(
            factory(Quote::class)->create(['quote_template_id' => $quoteTemplate->getKey()]),
            $attributes
        );
    }
}
