<?php

namespace Tests\Unit\Quote;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser
};
use Str, Arr;

class QuoteTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote, WithFakeQuoteFile;

    /** @var \App\Models\QuoteFile\QuoteFile */
    protected $quoteFile;

    /** @var array */
    protected static $assertableGroupDescriptionAttributes = ['id', 'name', 'search_text', 'total_count', 'total_price', 'rows'];

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

        $state = [
            'quote_id' => $this->quote->id
        ];

        $response = $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

        $response->assertForbidden()
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

        $state = [
            'quote_id' => $this->quote->id
        ];

        $response = $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

        $response->assertOk()
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

        $response = $this->putJson(url("api/quotes/submitted/unsubmit/{$this->quote->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $this->quote->refresh();

        $this->assertNull($this->quote->submitted_at);
    }

    /**
     * Test Copying Submitted Quote.
     *
     * @return void
     */
    public function testSubmittedQuoteCopying()
    {
        $this->quote->submit();

        $response = $this->putJson(url("api/quotes/submitted/copy/{$this->quote->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $this->quote->refresh();

        // The copied original Quote should be deactivated after copying.
        $this->assertNull($this->quote->activated_at);
    }


    /**
     * Test creating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionCreating()
    {
        $attributes = $this->makeGenericGroupDescriptionAttributes();

        $response = $this->postJson(url("api/quotes/groups/{$this->quote->id}"), $attributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'search_text'
            ]);
    }

    /**
     * Test updating Group Description with valid attributes.
     *
     * @return void
     */
    public function testGroupDescriptionUpdating()
    {
        $group = $this->createFakeGroupDescription();
        $id = $group->get('id');

        $attributes = $this->makeGenericGroupDescriptionAttributes();

        $response = $this->patchJson(url("api/quotes/groups/{$this->quote->id}/{$id}"), $attributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test deleting an existing Group Description.
     *
     * @return void
     */
    public function testGroupDescriptionDeleting()
    {
        $group = $this->createFakeGroupDescription();
        $id = $group->get('id');

        $response = $this->deleteJson(url("api/quotes/groups/{$this->quote->id}/{$id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test moving rows from one to another Group Description.
     *
     * @return void
     */
    public function testGroupDescriptionMovingRows()
    {
        $groups = collect()->times(2)->transform(function ($group) {
            return $this->createFakeGroupDescription();
        });

        $fromGroup = $groups->shift();
        $toGroup = $groups->shift();

        $attributes = [
            'from_group_id' => $fromGroup->get('id'),
            'to_group_id' => $toGroup->get('id'),
            'rows' => data_get($fromGroup, 'rows.*')
        ];

        $response = $this->putJson(url("api/quotes/groups/{$this->quote->id}"), $attributes, $this->authorizationHeader);

        $response->assertOk();

        $this->assertIsBool(json_decode($response->getContent(), true));
    }

    /**
     * Test displaying a newly created Group Description.
     *
     * @return void
     */
    public function testGroupDisplaying()
    {
        $group = $this->createFakeGroupDescription();
        $id = $group->get('id');

        $response = $this->getJson(url("api/quotes/groups/{$this->quote->id}/{$id}"), $this->authorizationHeader);

        $response->assertOk()
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

        collect()->times($count)->each(function ($group) {
            $this->createFakeGroupDescription();
        });

        $response = $this->getJson(url("api/quotes/groups/{$this->quote->id}"), $this->authorizationHeader);

        $response->assertOk();

        $collection = collect($response->json());

        $this->assertEquals($count, $collection->count());

        $this->assertTrue(
            Arr::has($collection->first(), static::$assertableGroupDescriptionAttributes)
        );
    }

    protected function createFakeGroupDescription(): Collection
    {
        $attributes = $this->makeGenericGroupDescriptionAttributes();
        $group = $this->quoteRepository->createGroupDescription($attributes, $this->quote->id);

        return tap($group)->put('rows', $attributes['rows']);
    }

    protected function makeGenericGroupDescriptionAttributes(): array
    {
        $rows = collect()->times(2)
            ->transform(function ($row) {
                return ['id' => (string) Str::uuid(4), 'user_id' => $this->user->id, 'quote_file_id' => $this->quoteFile->id];
            });

        \DB::table('imported_rows')->insert($rows->toArray());

        return [
            'name' => $this->faker->sentence(3),
            'search_text' => $this->faker->sentence(3),
            'rows' => $rows->pluck('id')->toArray()
        ];
    }
}
