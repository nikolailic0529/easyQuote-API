<?php

namespace Tests\Unit\Quote;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\Models\SND;
use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Domain\Rescue\DataTransferObjects\RowsGroup;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Models\TemplateField;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class RescueQuoteTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    protected static array $assertableGroupDescriptionAttributes = ['id', 'name', 'search_text', 'total_count', 'total_price', 'rows'];

    /**
     * Test an ability to view drafted quotes listing.
     *
     * @return void
     */
    public function testCanViewDraftedQuotesListing()
    {
        $this->authenticateApi();

        Quote::factory()->count(2)->create(['submitted_at' => null]);

        $this->get('api/quotes/drafted')->assertOk();
    }

    /**
     * Test an ability to view submitted quotes listing.
     *
     * @return void
     */
    public function testCanViewSubmittedQuotesListing()
    {
        $this->authenticateApi();

        Quote::factory()->count(2)->create(['submitted_at' => now()]);

        $this->get('api/quotes/submitted')->assertOk();
    }

    /**
     * Test updating an existing submitted quote.
     *
     * @return void
     */
    public function testUpdatingSubmittedQuote()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        $quote->submit();

        $state = ['quote_id' => $quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => QSU_01,
            ]);
    }

    /**
     * Test updating an existing drafted quote.
     *
     * @return void
     */
    public function testUpdatingDraftedQuote()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        $quote->unSubmit();

        $state = ['quote_id' => $quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertOk()
            ->assertExactJson(['id' => $quote->id]);
    }

    /**
     * Test moving quote to drafted.
     *
     * @return void
     */
    public function testUnravelQuote()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        $quote->submit();

        $this->putJson(url("api/quotes/submitted/unsubmit/{$quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($quote->refresh()->submitted_at);
    }

    /**
     * Test Copying Submitted Quote.
     *
     * @return void
     */
    public function testSubmittedQuoteCopying()
    {
        $this->authenticateApi();

        $quote = \App\Domain\Rescue\Models\Quote::factory()->for($this->app['auth']->user())->create();
        $quote->submit();

        $this->putJson(url("api/quotes/submitted/copy/{$quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        // The copied original Quote should be deactivated after copying.
        $this->assertNull($quote->refresh()->activated_at);
    }

    /**
     * Test creating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionCreating()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();

        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $attributes = $this->generateGroupDescription($quoteFile);

        $this->postJson(url("api/quotes/groups/{$quote->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text',
            ]);

        $gd = $quote->refresh()->group_description;

        $this->assertInstanceOf(Collection::class, $gd);

        $gd->each(fn ($group) => $this->assertInstanceOf(RowsGroup::class, $group));
    }

    /**
     * Test Group Description Setter.
     *
     * @return void
     */
    public function testGroupDescriptionSetter()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        $groups = collect()->times(100, fn () => new RowsGroup([
            'id' => (string) Str::uuid(),
            'name' => 'Group',
            'search_text' => '1234',
            'is_selected' => true,
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray(),
        ]));

        $quote->group_description = $groups;

        $this->assertTrue($quote->save());

        /**
         * All Groups must be instance of RowsGroup.
         */
        $groups = collect()->times(50, fn () => new RowsGroup([
            'id' => (string) Str::uuid(),
            'name' => 'Group',
            'search_text' => '1234',
            'is_selected' => true,
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray(),
        ]));

        $groups = collect()->times(50, fn () => Str::random());

        $this->expectException(JsonEncodingException::class);
        $this->expectExceptionMessageMatches('/The Collection must contain only values instance of/i');

        $quote->group_description = $groups;

        $this->assertTrue($quote->save());

        /**
         * It is allowed to set Groups Collection as Json string.
         */
        $groups = collect()->times(100, fn () => new RowsGroup([
            'id' => (string) Str::uuid(),
            'name' => 'Group',
            'search_text' => '1234',
            'is_selected' => true,
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray(),
        ]))->toJson();

        $quote->group_description = $groups;

        $this->assertTrue($quote->save());
    }

    /**
     * Test updating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionUpdating()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = \App\Domain\Rescue\Models\Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $attributes = $this->generateGroupDescription($quoteFile);

        $this->patchJson(url("api/quotes/groups/{$quote->id}/{$group->id}"), $attributes)
            ->assertOk()
            ->assertExactJson([true]);

        $gd = $quote->refresh()->group_description;

        $this->assertInstanceOf(Collection::class, $gd);

        $gd->each(fn ($group) => $this->assertInstanceOf(RowsGroup::class, $group));
    }

    /**
     * Test deleting an existing Group Description.
     *
     * @return void
     */
    public function testGroupDescriptionDeleting()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $this->deleteJson(url("api/quotes/groups/{$quote->id}/{$group->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test moving rows from one to another Group Description.
     *
     * @return void
     */
    public function testGroupDescriptionMovingRows()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $groups = collect()->times(2)->transform(fn () => $this->createFakeGroupDescription($quote, $quoteFile));

        /** @var RowsGroup */
        $fromGroup = $groups->shift();

        /** @var \App\Domain\Rescue\DataTransferObjects\RowsGroup */
        $toGroup = $groups->shift();

        $attributes = [
            'from_group_id' => $fromGroup->id,
            'to_group_id' => $toGroup->id,
            'rows' => $fromGroup->rows_ids,
        ];

        $response = $this->putJson(url("api/quotes/groups/{$quote->id}"), $attributes)->assertOk();

        $this->assertIsBool(json_decode($response->getContent(), true));

        $gd = $quote->refresh()->group_description;

        $this->assertInstanceOf(Collection::class, $gd);

        $gd->each(fn ($group) => $this->assertInstanceOf(RowsGroup::class, $group));
    }

    /**
     * Test displaying a newly created Group Description.
     *
     * @return void
     */
    public function testGroupDisplaying()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $this->getJson(url("api/quotes/groups/{$quote->id}/{$group->id}"))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text', 'total_count', 'total_price', 'rows',
            ]);
    }

    /**
     * Test Group Description listing.
     *
     * @return void
     */
    public function testGroupDescriptionListing()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = \App\Domain\Rescue\Models\Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $count = 10;

        collect()->times($count)->each(fn () => $this->createFakeGroupDescription($quote, $quoteFile));

        $response = $this->getJson(url("api/quotes/groups/{$quote->id}"))->assertOk();

        $collection = collect($response->json());

        $this->assertCount($count, $collection);

        $this->assertTrue(
            Arr::has($collection->first(), static::$assertableGroupDescriptionAttributes)
        );
    }

    /**
     * Test Group Description selecting.
     *
     * @return void
     */
    public function testGroupDescriptionSelecting()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $count = 10;

        $groups = collect()->times($count)->map(fn ($group) => $this->createFakeGroupDescription($quote, $quoteFile));

        $selected = $groups->random(mt_rand(1, $count))->keyBy('id');

        $this->putJson(url("api/quotes/groups/{$quote->id}/select"), $selected->pluck('id')->toArray())
            ->assertOk();

        $groups = Collection::wrap($quote->refresh()->group_description);

        /*
         * Assert that groups actual is_selected attribute is matching to selected groups.
         */
        $groups->each(fn (RowsGroup $group) => $this->assertEquals($selected->has($group->id), $group->is_selected));

        /*
         * Assert that quote review endpoint is displaying selected groups.
         */
        $quote->update(['use_groups' => true]);

        $response = $this->get(url("api/quotes/review/{$quote->id}"));

        $reviewData = $response->json();

        $reviewGroupsIds = data_get($reviewData, 'data_pages.rows.*.id');

        $selected->each(fn (RowsGroup $group) => $this->assertTrue(in_array($group->id, $reviewGroupsIds)));
    }

    /**
     * Test an ability to view correct total list price of the rescue quote,
     * when the rows are selected.
     */
    public function testCanViewCorrectTotalListPriceOfRescueQuoteWhenRowsAreSelected()
    {
        $this->authenticateApi();

        $templateFields = TemplateField::query()->where('is_system', true)->get()->mapWithKeys(fn (TemplateField $f) => [$f->name => $f->getKey()]);
        $importableColumns = ImportableColumn::query()->where('is_system', true)->get()->mapWithKeys(fn (ImportableColumn $f) => [$f->name => $f->getKey()]);

        /** @var \App\Domain\Rescue\Models\Quote $quote */
        $quote = factory(\App\Domain\Rescue\Models\Quote::class)->create([
            'calculate_list_price' => false,
            'source_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
            'target_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
        ]);

        $quote->templateFields()->sync([
            $templateFields->get('product_no') => ['importable_column_id' => $importableColumns->get('product_no')],
            $templateFields->get('serial_no') => ['importable_column_id' => $importableColumns->get('serial_no')],
            $templateFields->get('description') => ['importable_column_id' => $importableColumns->get('description')],
            $templateFields->get('date_from') => ['importable_column_id' => $importableColumns->get('date_from')],
            $templateFields->get('date_to') => ['importable_column_id' => $importableColumns->get('date_to')],
            $templateFields->get('qty') => ['importable_column_id' => $importableColumns->get('qty')],
            $templateFields->get('price') => ['importable_column_id' => $importableColumns->get('price')],
        ]);

        $quoteFile = factory(QuoteFile::class)->create([
            'file_type' => 'Distributor Price List',
        ]);

        $quote->priceList()->associate($quoteFile)->save();

        /** @var \App\Domain\QuoteFile\Models\ImportedRow $importedRow */
        $importedRow = factory(ImportedRow::class)->create([
            'quote_file_id' => $quoteFile->getKey(),
            'columns_data' => [
                $templateFields->get('product_no') => ['value' => $this->faker->regexify('/\d{6}-[A-Z]\d{2}/'), 'header' => 'Product Number', 'importable_column_id' => $importableColumns->get('product_no')],
                $templateFields->get('serial_no') => ['value' => $this->faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'), 'header' => 'Serial Number', 'importable_column_id' => $importableColumns->get('serial_no')],
                $templateFields->get('description') => ['value' => $this->faker->text, 'header' => 'Description', 'importable_column_id' => $importableColumns->get('description')],
                $templateFields->get('date_from') => ['value' => now()->format('d/m/Y'), 'header' => 'Coverage from', 'importable_column_id' => $importableColumns->get('date_from')],
                $templateFields->get('date_to') => ['value' => now()->addYears(2)->format('d/m/Y'), 'header' => 'Coverage to', 'importable_column_id' => $importableColumns->get('date_to')],
                $templateFields->get('qty') => ['value' => 1, 'header' => 'Quantity', 'importable_column_id' => $importableColumns->get('qty')],
                $templateFields->get('price') => ['value' => 1000, 'header' => 'Price', 'importable_column_id' => $importableColumns->get('price')],
            ],
            'is_selected' => true,
        ]);

        $this->putJson('api/quotes/get/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'list_price',
            ])
            ->assertJson([
                'list_price' => '1,000.00',
            ]);
    }

    /**
     * Test an ability to view correct total list price of the rescue quote,
     * when the grouped contain the same rows.
     */
    public function testCanViewCorrectTotalListPriceOfRescueQuoteWhenSameRowsAreGrouped()
    {
        $this->authenticateApi();

        $templateFields = TemplateField::query()->where('is_system', true)->get()->mapWithKeys(fn (TemplateField $f) => [$f->name => $f->getKey()]);
        $importableColumns = ImportableColumn::query()->where('is_system', true)->get()->mapWithKeys(fn (ImportableColumn $f) => [$f->name => $f->getKey()]);

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'use_groups' => true,
            'calculate_list_price' => false,
            'source_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
            'target_currency_id' => Currency::query()->where('code', 'GBP')->sole()->getKey(),
        ]);

        $quote->templateFields()->sync([
            $templateFields->get('product_no') => ['importable_column_id' => $importableColumns->get('product_no')],
            $templateFields->get('serial_no') => ['importable_column_id' => $importableColumns->get('serial_no')],
            $templateFields->get('description') => ['importable_column_id' => $importableColumns->get('description')],
            $templateFields->get('date_from') => ['importable_column_id' => $importableColumns->get('date_from')],
            $templateFields->get('date_to') => ['importable_column_id' => $importableColumns->get('date_to')],
            $templateFields->get('qty') => ['importable_column_id' => $importableColumns->get('qty')],
            $templateFields->get('price') => ['importable_column_id' => $importableColumns->get('price')],
        ]);

        $quoteFile = factory(QuoteFile::class)->create([
            'file_type' => 'Distributor Price List',
        ]);

        $quote->priceList()->associate($quoteFile)->save();

        /** @var ImportedRow $importedRow */
        $importedRow = factory(ImportedRow::class)->create([
            'quote_file_id' => $quoteFile->getKey(),
            'columns_data' => [
                $templateFields->get('product_no') => ['value' => $this->faker->regexify('/\d{6}-[A-Z]\d{2}/'), 'header' => 'Product Number', 'importable_column_id' => $importableColumns->get('product_no')],
                $templateFields->get('serial_no') => ['value' => $this->faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'), 'header' => 'Serial Number', 'importable_column_id' => $importableColumns->get('serial_no')],
                $templateFields->get('description') => ['value' => $this->faker->text, 'header' => 'Description', 'importable_column_id' => $importableColumns->get('description')],
                $templateFields->get('date_from') => ['value' => now()->format('d/m/Y'), 'header' => 'Coverage from', 'importable_column_id' => $importableColumns->get('date_from')],
                $templateFields->get('date_to') => ['value' => now()->addYears(2)->format('d/m/Y'), 'header' => 'Coverage to', 'importable_column_id' => $importableColumns->get('date_to')],
                $templateFields->get('qty') => ['value' => 1, 'header' => 'Quantity', 'importable_column_id' => $importableColumns->get('qty')],
                $templateFields->get('price') => ['value' => 1000, 'header' => 'Price', 'importable_column_id' => $importableColumns->get('price')],
            ],
        ]);

        $groups = collect([
            RowsGroup::make([
                'name' => Str::random(),
                'search_text' => Str::random(),
                'rows_ids' => [$importedRow->getKey()],
                'is_selected' => true,
            ]),
            RowsGroup::make([
                'name' => Str::random(),
                'search_text' => Str::random(),
                'rows_ids' => [$importedRow->getKey()],
                'is_selected' => true,
            ]),
        ]);

        $quote->group_description = $groups;

        $quote->save();

        $this->putJson('api/quotes/get/'.$quote->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'list_price',
            ])
            ->assertJson([
                'list_price' => '2,000.00',
            ]);
    }

    /**
     * Test Submitted Quote Export in PDF.
     *
     * @return void
     */
    public function testSubmittedQuoteExport()
    {
        $this->authenticateApi();

        $quoteTemplate = factory(QuoteTemplate::class)->create([
            'company_id' => Company::query()->where('short_code', 'EPD')->value('id'),
        ]);

        $quote = factory(Quote::class)->create([
            'user_id' => $this->app['auth.driver']->id(),
            'quote_template_id' => $quoteTemplate->getKey(),
            'company_id' => Company::query()->where('short_code', 'EPD')->value('id'),
            'submitted_at' => now(),
        ]);

        $response = $this->getJson(url("api/quotes/submitted/pdf/{$quote->id}"));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test download quote distributor file.
     *
     * @return void
     */
    public function testDownloadQuoteDistributorFile()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();

        Storage::fake();

        $filePath = $this->app['auth']->user()->quote_files_directory.'/'.Str::random(40).'.pdf';

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
     * Test download quote payment schedule file.
     *
     * @return void
     */
    public function testDownloadQuoteScheduleFile()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();

        Storage::fake();

        $filePath = $this->app['auth']->user()->quote_files_directory.'/'.Str::random(40).'.pdf';

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
     * Test quote retrieving with enabled default template fields.
     *
     * @return void
     */
    public function testQuoteRetrievingWithEnabledDefaultTemplateFields()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $rows = factory(ImportedRow::class, 200)->create(['quote_file_id' => $quoteFile->id, 'is_selected' => true]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $defaults = ['date_from' => true, 'date_to' => true];

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name), 'is_default_enabled' => Arr::get($defaults, $name, false)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/'.$quote->id)
//            ->dump()
            ->assertOk();

        $this->getJson('api/quotes/review/'.$quote->id)
//            ->dump()
            ->assertOk();
    }

    /**
     * Test quote retrieving with full mapped columns.
     *
     * @return void
     */
    public function testQuoteRetrievingWithFullMappedColumns()
    {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = \App\Domain\Rescue\Models\Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();
        $rows = factory(ImportedRow::class, 20)->create(['quote_file_id' => $quoteFile->id, 'is_selected' => true]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/'.$quote->id)
//            ->dump()
            ->assertOk();

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
     * Test an ability to view preview data of quote.
     *
     * @dataProvider previewDataProvider
     */
    public function testCanViewPreviewDataOfQuote(
        string $countryCode,
        string $currencyCode,
        string $currencySymbol,
        string $expectedDateFormat
    ): void {
        $this->authenticateApi();

        $quoteFile = factory(QuoteFile::class)->create();
        $quote = Quote::factory()
            ->for($this->app['auth']->user())
            ->for($quoteFile, relationship: 'priceList')
            ->create();

        $priceList = factory(QuoteFile::class)->create([
            'file_type' => 'Distributor Price List',
            'imported_page' => 1,
        ]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        /** @var \App\Domain\QuoteFile\Models\ImportedRow $row */
        $row = factory(ImportedRow::class)->create([
            'quote_file_id' => $priceList->getKey(),
            'columns_data' => [
                $templateFields->get('product_no') => ['value' => $this->faker->regexify('/\d{6}-[A-Z]\d{2}/'), 'header' => 'Product Number', 'importable_column_id' => $importableColumns->get('product_no')],
                $templateFields->get('serial_no') => ['value' => $this->faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'), 'header' => 'Serial Number', 'importable_column_id' => $importableColumns->get('serial_no')],
                $templateFields->get('description') => ['value' => $this->faker->text, 'header' => 'Description', 'importable_column_id' => $importableColumns->get('description')],
                $templateFields->get('date_from') => ['value' => now()->format('d/m/Y'), 'header' => 'Coverage from', 'importable_column_id' => $importableColumns->get('date_from')],
                $templateFields->get('date_to') => ['value' => now()->addYears(2)->format('d/m/Y'), 'header' => 'Coverage to', 'importable_column_id' => $importableColumns->get('date_to')],
                $templateFields->get('qty') => ['value' => 1, 'header' => 'Quantity', 'importable_column_id' => $importableColumns->get('qty')],
                $templateFields->get('price') => ['value' => '1,192.00', 'header' => 'Price', 'importable_column_id' => $importableColumns->get('price')],
            ],
            'page' => 1,
            'is_selected' => true,
        ]);

        $paymentSchedule = factory(QuoteFile::class)->create([
            'file_type' => 'Payment Schedule',
            'imported_page' => 2,
        ]);

        $scheduleData = factory(ScheduleData::class)->create([
            'quote_file_id' => $paymentSchedule->getKey(),
            'value' => [
                [
                    'from' => '01.01.2020',
                    'to' => '30.01.2020',
                    'price' => '10.000,00',
                ],
                [
                    'from' => '31.01.2020',
                    'to' => '30.01.2021',
                    'price' => '12.000,00',
                ],
            ],
        ]);

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'distributor_file_id' => $priceList->getKey(),
            'schedule_file_id' => $paymentSchedule->getKey(),
            'source_currency_id' => Currency::query()->where('code', $currencyCode)->value('id'),
            'target_currency_id' => null,
            'calculate_list_price' => false,
            'custom_discount' => 2,
            'buy_price' => 1_100.0,
            'customer_id' => factory(Customer::class)->create($customerData = [
                'support_start' => '2022-12-30',
                'support_end' => '2023-12-31',
                'valid_until' => '2022-12-30',
                'country_id' => Country::query()->where('iso_3166_2', $countryCode)->first()->getKey(),
            ])->getKey(),
        ]);

        $quote->templateFields()->sync([
            $templateFields->get('product_no') => ['importable_column_id' => $importableColumns->get('product_no')],
            $templateFields->get('serial_no') => ['importable_column_id' => $importableColumns->get('serial_no')],
            $templateFields->get('description') => ['importable_column_id' => $importableColumns->get('description')],
            $templateFields->get('date_from') => ['importable_column_id' => $importableColumns->get('date_from')],
            $templateFields->get('date_to') => ['importable_column_id' => $importableColumns->get('date_to')],
            $templateFields->get('qty') => ['importable_column_id' => $importableColumns->get('qty')],
            $templateFields->get('price') => ['importable_column_id' => $importableColumns->get('price')],
        ]);

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
                    'list_price',
                    'applicable_discounts',
                    'final_price',
                    'invoicing_terms',
                    'full_name',
                    'date',
                    'service_agreement_id',
                    'system_handle',
                ],
                'data_pages' => [
                    'pricing_document',
                    'service_agreement_id',
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
                    ],
                    'rows' => [
                        '*' => [
                            'id',
                            'is_selected',
                            'product_no',
                            'description',
                            'date_from',
                            'date_to',
                            'qty',
                            'price',
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

        $this->assertSame("$currencySymbol 1,192.00", $response->json('first_page.list_price'));
        $this->assertSame("$currencySymbol 25.29", $response->json('first_page.applicable_discounts'));
        $this->assertSame("$currencySymbol 1,166.71", $response->json('first_page.final_price'));
        $this->assertSame("$currencySymbol 530.32", $response->json('payment_schedule.data.0.price'));
        $this->assertSame("$currencySymbol 636.39", $response->json('payment_schedule.data.1.price'));

        $this->assertSame(Carbon::parse($customerData['support_start'])->format($expectedDateFormat), $response->json('first_page.support_start'));
        $this->assertSame(Carbon::parse($customerData['support_end'])->format($expectedDateFormat), $response->json('first_page.support_end'));
        $this->assertSame(Carbon::parse($customerData['valid_until'])->format($expectedDateFormat), $response->json('first_page.valid_until'));
        $this->assertSame(Carbon::parse($customerData['support_start'])->format($expectedDateFormat), $response->json('data_pages.coverage_period_from'));
        $this->assertSame(Carbon::parse($customerData['support_end'])->format($expectedDateFormat), $response->json('data_pages.coverage_period_to'));

        $dateFrom = $row->columns_data->where('header', 'Coverage from')->sole()->value;
        $dateTo = $row->columns_data->where('header', 'Coverage to')->sole()->value;

        $this->assertSame(Carbon::createFromFormat('d/m/Y', $dateFrom)->format($expectedDateFormat), $response->json('data_pages.rows.0.date_from'));
        $this->assertSame(Carbon::createFromFormat('d/m/Y', $dateTo)->format($expectedDateFormat), $response->json('data_pages.rows.0.date_to'));
    }

    protected function previewDataProvider(): \Generator
    {
        yield 'US' => [
            'US',
            'USD',
            '$',
            'm/d/Y',
        ];

        yield 'CA' => [
            'CA',
            'CAD',
            'CA$',
            'm/d/Y',
        ];

        yield 'GB' => [
            'GB',
            'GBP',
            '£',
            'd/m/Y',
        ];

        yield 'FR' => [
            'FR',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'PL' => [
            'PL',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'BE' => [
            'BE',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'NL' => [
            'NL',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'SE' => [
            'SE',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'AT' => [
            'AT',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'IE' => [
            'IE',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'NO' => [
            'NO',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'ZA' => [
            'ZA',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'DK' => [
            'DK',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'CZ' => [
            'CZ',
            'EUR',
            '€',
            'd/m/Y',
        ];

        yield 'CH' => [
            'CH',
            'EUR',
            '€',
            'd/m/Y',
        ];
    }

    /**
     * Test an ability to perform quote file import.
     *
     * @return void
     */
    public function testCanPerformQuoteFileImport()
    {
        $this->authenticateApi();

        $rescueQuote = factory(\App\Domain\Rescue\Models\Quote::class)->create();

        $file = UploadedFile::fake()->createWithContent(base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf'), file_get_contents(base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf')));

        $quoteFileID = $this->postJson('/api/quotes/file', [
            'file_type' => 'Distributor Price List',
            'quote_file' => $file,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'pages',
                'original_file_path',
                'original_file_name',
                'file_type',
                'quote_file_format_id',
                'user_id',
                'created_at',
                'updated_at',
                'format' => [
                    'id', 'name', 'extension',
                ],
            ])
            ->json('id');

        $this->postJson('api/quotes/handle', [
            'quote_id' => $rescueQuote->getKey(),
            'quote_file_id' => $quoteFileID,
            'page' => 1,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'status',
            ]);
    }

    public function testCanCalculateDiscountsForRescueQuote()
    {
        $this->authenticateApi();

        $quote = factory(Quote::class)->create();
        $snd = factory(SND::class)->create();

        $this->postJson('api/quotes/try-discounts/'.$quote->getKey(), [
            [
                'id' => $snd->getKey(),
            ],
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    '*' => [
                        'id',
                        'duration',
                        'margin_percentage',
                        'discountable',
                    ],
                ],
            ]);
    }

    protected function createFakeGroupDescription(Quote $quote, QuoteFile $quoteFile): RowsGroup
    {
        $attributes = $this->generateGroupDescription($quoteFile);
        /** @var \App\Domain\Rescue\DataTransferObjects\RowsGroup */
        $group = app(QuoteState::class)->createGroupDescription($attributes, $quote);

        return tap($group, fn () => $group->rows_ids = $attributes['rows']);
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
