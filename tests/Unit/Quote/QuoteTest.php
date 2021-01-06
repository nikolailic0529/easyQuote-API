<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser,
};
use App\DTO\RowsGroup;
use Illuminate\Support\Collection;
use App\Models\{
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\ImportedRow,
    Template\TemplateField,
};
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Str, Arr};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @group build
 */
class QuoteTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, WithFakeQuoteFile, DatabaseTransactions;

    protected ?QuoteFile $quoteFile = null;

    protected static array $assertableGroupDescriptionAttributes = ['id', 'name', 'search_text', 'total_count', 'total_price', 'rows'];

    protected array $truncatableTables = [
        'quotes'
    ];

    public function testDraftedQuotesListing()
    {
        $this->get('api/quotes/drafted')->assertOk();
    }

    public function testSubmittedQuotesListing()
    {
        $this->get('api/quotes/submitted')->assertOk();
    }

    /**
     * Test updating an existing submitted quote.
     *
     * @return void
     */
    public function testUpdatingSubmittedQuote()
    {
        $quote = $this->createQuote($this->user);
        $quote->submit();

        $state = ['quote_id' => $quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => QSU_01
            ]);
    }

    /**
     * Test updating an existing drafted quote.
     *
     * @return void
     */
    public function testUpdatingDraftedQuote()
    {
        $quote = $this->createQuote($this->user);
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
        $quote = $this->createQuote($this->user);
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
        $quote = $this->createQuote($this->user);
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $attributes = $this->generateGroupDescription($quoteFile);

        $this->postJson(url("api/quotes/groups/{$quote->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text'
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
        $quote = $this->createQuote($this->user);
        $groups = collect()->times(100, fn () => new RowsGroup([
            'id' => (string) Str::uuid(),
            'name' => 'Group',
            'search_text' => '1234',
            'is_selected' => true,
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray()
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
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray()
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
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray()
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $groups = collect()->times(2)->transform(fn () => $this->createFakeGroupDescription($quote, $quoteFile));

        /** @var RowsGroup */
        $fromGroup = $groups->shift();

        /** @var RowsGroup */
        $toGroup = $groups->shift();

        $attributes = [
            'from_group_id' => $fromGroup->id,
            'to_group_id' => $toGroup->id,
            'rows' => $fromGroup->rows_ids
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $group = $this->createFakeGroupDescription($quote, $quoteFile);

        $this->getJson(url("api/quotes/groups/{$quote->id}/{$group->id}"))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text', 'total_count', 'total_price', 'rows'
            ]);
    }

    /**
     * Test Group Description listing.
     *
     * @return void
     */
    public function testGroupDescriptionListing()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $count = 10;

        $groups = collect()->times($count)->map(fn ($group) => $this->createFakeGroupDescription($quote, $quoteFile));

        $selected = $groups->random(mt_rand(1, $count))->keyBy('id');

        $this->putJson(url("api/quotes/groups/{$quote->id}/select"), $selected->pluck('id')->toArray())
            ->assertOk();

        $groups = Collection::wrap($quote->refresh()->group_description);

        /**
         * Assert that groups actual is_selected attribute is matching to selected groups.
         */
        $groups->each(fn (RowsGroup $group) => $this->assertEquals($selected->has($group->id), $group->is_selected));

        /**
         * Assert that quote review endpoint is displaying selected groups.
         */
        $quote->update(['use_groups' => true]);

        $response = $this->get(url("api/quotes/review/{$quote->id}"));

        $reviewData = $response->json();

        $reviewGroupsIds = data_get($reviewData, 'data_pages.rows.*.id');

        $selected->each(fn (RowsGroup $group) => $this->assertTrue(in_array($group->id, $reviewGroupsIds)));
    }

    /**
     * Test Submitted Quote Export in PDF.
     *
     * @return void
     */
    public function testSubmittedQuoteExport()
    {
        $quote = $this->createQuote($this->user);
        $quote->submit();

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
        $quote = $this->createQuote($this->user);

        Storage::fake();

        $filePath = $this->user->quote_files_directory . '/' . Str::random(40) . '.pdf';

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
        $quote = $this->createQuote($this->user);

        Storage::fake();

        $filePath = $this->user->quote_files_directory . '/' . Str::random(40) . '.pdf';

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
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $rows = factory(ImportedRow::class, 200)->create(['quote_file_id' => $quoteFile->id]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $defaults = ['date_from' => true, 'date_to' => true];

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name), 'is_default_enabled' => Arr::get($defaults, $name, false)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/' . $quote->id)->assertOk();

        $this->getJson('api/quotes/review/' . $quote->id)->assertOk();
    }

    /**
     * Test quote retrieving with full mapped columns.
     *
     * @return void
     */
    public function testQuoteRetrievingWithFullMappedColumns()
    {
        $quote = $this->createQuote($this->user);
        $quoteFile = $this->createFakeQuoteFile($quote);
        $rows = factory(ImportedRow::class, 20)->create(['quote_file_id' => $quoteFile->id, 'is_selected' => true]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name)]);

        $quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/' . $quote->id)->assertOk();

        $this->getJson('api/quotes/review/' . $quote->id)
            ->assertOk()
            ->assertJsonStructure([
                'first_page',
                'last_page',
                'data_pages' => [
                    'rows_header' => ['product_no', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'searchable'],
                    'rows' => [
                        '*' => ['id', 'is_selected', 'product_no', 'serial_no', 'description', 'date_from', 'date_to', 'qty', 'price', 'searchable']
                    ]
                ],
                'payment_schedule'
            ]);
    }

    protected function createFakeGroupDescription(Quote $quote, QuoteFile $quoteFile): RowsGroup
    {
        $attributes = $this->generateGroupDescription($quoteFile);
        /** @var \App\DTO\RowsGroup */
        $group = $this->quoteRepository->createGroupDescription($attributes, $quote);

        return tap($group, fn () => $group->rows_ids = $attributes['rows']);
    }

    protected function generateGroupDescription(QuoteFile $quoteFile): array
    {
        $group_name = $this->faker->unique()->sentence(3);

        $rows = factory(ImportedRow::class, 4)->create(['quote_file_id' => $quoteFile->id]);

        return [
            'name'          => $group_name,
            'search_text'   => $this->faker->sentence(3),
            'rows'          => $rows->pluck('id')->toArray()
        ];
    }
}
