<?php

namespace Tests\Unit\Quote;

use App\DTO\RowsGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\Traits\{
    TruncatesDatabaseTables,
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser,
};
use App\Models\{
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\ImportedRow,
    QuoteTemplate\TemplateField,
};
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\{Str, Arr};

class QuoteTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, WithFakeQuoteFile, TruncatesDatabaseTables;

    protected ?QuoteFile $quoteFile = null;

    protected static array $assertableGroupDescriptionAttributes = ['id', 'name', 'search_text', 'total_count', 'total_price', 'rows'];

    protected array $truncatableTables = [
        'quotes'
    ];

    protected function setUp(): void
    {
        parent::{__FUNCTION__}();

        $this->quoteFile = $this->createFakeQuoteFile($this->quote);
    }

    /**
     * Test updating an existing submitted quote.
     *
     * @return void
     */
    public function testUpdatingSubmittedQuote()
    {
        $this->quote->submit();

        $state = ['quote_id' => $this->quote->id];

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
        $this->quote->unSubmit();

        $state = ['quote_id' => $this->quote->id];

        $this->postJson(url('api/quotes/state'), $state)
            ->assertOk()
            ->assertExactJson(['id' => $this->quote->id]);
    }

    /**
     * Test moving quote to drafted.
     *
     * @return void
     */
    public function testUnsubmitQuote()
    {
        $this->quote->submit();

        $this->putJson(url("api/quotes/submitted/unsubmit/{$this->quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($this->quote->refresh()->submitted_at);
    }

    /**
     * Test Copying Submitted Quote.
     *
     * @return void
     */
    public function testSubmittedQuoteCopying()
    {
        $this->quote->submit();

        $this->putJson(url("api/quotes/submitted/copy/{$this->quote->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        // The copied original Quote should be deactivated after copying.
        $this->assertNull($this->quote->refresh()->activated_at);
    }


    /**
     * Test creating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionCreating()
    {
        $attributes = $this->makeGenericGroupDescriptionAttributes();

        $this->postJson(url("api/quotes/groups/{$this->quote->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text'
            ]);

        $gd = $this->quote->refresh()->group_description;

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
        $groups = collect()->times(100, fn () => new RowsGroup([
            'id' => (string) Str::uuid(),
            'name' => 'Group',
            'search_text' => '1234',
            'is_selected' => true,
            'rows_ids' => collect()->times(600, fn () => (string) Str::uuid())->toArray()
        ]));

        $this->quote->group_description = $groups;

        $this->assertTrue($this->quote->save());

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

        $this->quote->group_description = $groups;

        $this->assertTrue($this->quote->save());

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

        $this->quote->group_description = $groups;

        $this->assertTrue($this->quote->save());
    }

    /**
     * Test updating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionUpdating()
    {
        $group = $this->createFakeGroupDescription();

        $attributes = $this->makeGenericGroupDescriptionAttributes();

        $this->patchJson(url("api/quotes/groups/{$this->quote->id}/{$group->id}"), $attributes)
            ->assertOk()
            ->assertExactJson([true]);

        $gd = $this->quote->refresh()->group_description;

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
        $group = $this->createFakeGroupDescription();

        $this->deleteJson(url("api/quotes/groups/{$this->quote->id}/{$group->id}"), [])
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
        $groups = collect()->times(2)->transform(fn () => $this->createFakeGroupDescription());

        /** @var RowsGroup */
        $fromGroup = $groups->shift();

        /** @var RowsGroup */
        $toGroup = $groups->shift();

        $attributes = [
            'from_group_id' => $fromGroup->id,
            'to_group_id' => $toGroup->id,
            'rows' => $fromGroup->rows_ids
        ];

        $response = $this->putJson(url("api/quotes/groups/{$this->quote->id}"), $attributes)->assertOk();

        $this->assertIsBool(json_decode($response->getContent(), true));

        $gd = $this->quote->refresh()->group_description;

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
        $group = $this->createFakeGroupDescription();

        $this->getJson(url("api/quotes/groups/{$this->quote->id}/{$group->id}"))
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
        $count = 10;

        collect()->times($count)->each(fn () => $this->createFakeGroupDescription());

        $response = $this->getJson(url("api/quotes/groups/{$this->quote->id}"))->assertOk();

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
        $count = 10;

        $groups = collect()->times($count)->map(fn ($group) => $this->createFakeGroupDescription());

        $selected = $groups->random(mt_rand(1, $count))->keyBy('id');

        $this->putJson(url("api/quotes/groups/{$this->quote->id}/select"), $selected->pluck('id')->toArray())
            ->assertOk();

        $groups = Collection::wrap($this->quote->refresh()->group_description);

        /**
         * Assert that groups actual is_selected attribute is matching to selected groups.
         */
        $groups->each(fn (RowsGroup $group) => $this->assertEquals($selected->has($group->id), $group->is_selected));

        /**
         * Assert that quote review endpoint is displaying selected groups.
         */
        $this->quote->update(['use_groups' => true]);

        $response = $this->get(url("api/quotes/review/{$this->quote->id}"));

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
        $this->quote->submit();

        $response = $this->getJson(url("api/quotes/submitted/pdf/{$this->quote->id}"));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test quote retrieving with enabled default template fields.
     *
     * @return void
     */
    public function testQuoteRetrievingWithEnabledDefaultTemplateFields()
    {
        $rows = factory(ImportedRow::class, 200)->create(['quote_file_id' => $this->quoteFile->id, 'user_id' => $this->user->id]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $defaults = ['date_from' => true, 'date_to' => true];

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name), 'is_default_enabled' => Arr::get($defaults, $name, false)]);

        $this->quote->templateFields()->sync($map->toArray());

        $this->putJson('/api/quotes/get/' . $this->quote->id)->assertOk();

        $this->getJson('api/quotes/review/' . $this->quote->id)->assertOk();
    }

    /**
     * Test quote retrieving with full mapped columns.
     *
     * @return void
     */
    public function testQuoteRetrievingWithFullMappedColumns()
    {
        $rows = factory(ImportedRow::class, 200)->create(['quote_file_id' => $this->quoteFile->id, 'user_id' => $this->user->id]);

        $templateFields = TemplateField::where('is_system', true)->pluck('id', 'name');
        $importableColumns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        $map = $templateFields->flip()->map(fn ($name, $id) => ['importable_column_id' => $importableColumns->get($name)]);

        $this->quote->templateFields()->sync($map->toArray());

        $response = $this->putJson('/api/quotes/get/' . $this->quote->id)->assertOk();

        $response = $this->getJson('api/quotes/review/' . $this->quote->id)->assertOk();
    }

    protected function createFakeGroupDescription(): RowsGroup
    {
        $attributes = $this->makeGenericGroupDescriptionAttributes();
        /** @var \App\DTO\RowsGroup */
        $group = $this->quoteRepository->createGroupDescription($attributes, $this->quote);

        return tap($group, fn () => $group->rows_ids = $attributes['rows']);
    }

    protected function makeGenericGroupDescriptionAttributes(): array
    {
        $group_name = $this->faker->unique()->sentence(3);

        $rows = factory(ImportedRow::class, 4)->create(compact('group_name') + ['quote_file_id' => $this->quoteFile->id, 'user_id' => $this->user->id]);

        return [
            'name'          => $group_name,
            'search_text'   => $this->faker->sentence(3),
            'rows'          => $rows->pluck('id')->toArray()
        ];
    }
}
