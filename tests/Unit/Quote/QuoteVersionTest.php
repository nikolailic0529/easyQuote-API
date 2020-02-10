<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeUser
};
use App\Models\Quote\Quote;
use Illuminate\Support\Carbon;

class QuoteVersionTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote;

    /**
     * Test a new Version creating when non-author user is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByNonAuthor()
    {
        $this->updateQuoteStateFromNewUser();

        $versionsCount = $this->quote->versions()->count();
        $this->assertGreaterThan(0, $versionsCount);
    }

    /**
     * Test no Version creating when version author is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByAuthor()
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $this->postJson(url('api/quotes/state'), $state);

        $versionsCount = $this->quote->versions()->count();
        $this->assertEquals(0, $versionsCount);
    }

    /**
     * Test Versions creating from different causers.
     *
     * @return void
     */
    public function testVersionCreatingFromDifferentCausers()
    {
        $expectedVersionsCount = 3;

        collect()->times($expectedVersionsCount)->each(fn () => $this->updateQuoteStateFromNewUser());

        $actualVersionsCount = $this->quote->versions()->count();

        $this->assertEquals($expectedVersionsCount, $actualVersionsCount);
    }

    /**
     * Test assigning a newly created Quote Version.
     *
     * @return void
     */
    public function testVersionSet()
    {
        $this->updateQuoteStateFromNewUser();

        $nonUsingVersion = $this->quote->versionsSelection->firstWhere('is_using', '===', false);

        $version_id = $nonUsingVersion['id'];

        $response = $this->patchJson(url("api/quotes/version/{$this->quote->id}"), compact('version_id'));

        $response->assertOk()->assertExactJson([true]);

        $actualUsingVersion = $this->quote->load('usingVersion')->usingVersion;

        $this->assertEquals($version_id, $actualUsingVersion->id);
    }

    /**
     * Test deleting a newly created Quote Version.
     *
     * @return void
     */
    public function testVersionDeleting()
    {
        $this->updateQuoteStateFromNewUser();

        $version = $this->quote->versions()->first();

        $response = $this->deleteJson(url("api/quotes/drafted/version/{$version->id}"));

        $response->assertOk()->assertExactJson([true]);

        $this->assertSoftDeleted($version);
    }

    public function testVersionUpdatingWithNewAttributes()
    {
        $user = tap($this->createUser(), fn ($user) => $this->actingAs($user, 'api'));

        $state = factory(Quote::class, 'state')->raw(['quote_id' => $this->quote->id]);

        $response = $this->postJson(url('api/quotes/state'), $state);

        $response->assertOk()
            ->assertExactJson(['id' => $this->quote->id]);

        $this->quote->usingVersion->refresh();

        $expectedQuoteAttributes = $state['quote_data'];
        $expectedQuoteAttributes['closing_date'] = Carbon::createFromFormat('Y-m-d', $expectedQuoteAttributes['closing_date'])->format('d/m/Y');

        $assertableAttributes = array_keys($expectedQuoteAttributes);
        $actualQuoteAttributes = $this->quote->usingVersion->only($assertableAttributes);

        $this->assertEquals($actualQuoteAttributes, $expectedQuoteAttributes);

        $expectedMarginAttributes = $state['margin'];

        $actualMarginAttributes = $this->quote->usingVersion
            ->countryMargin->only(array_keys($expectedMarginAttributes));

        $this->assertEquals($actualMarginAttributes, $expectedMarginAttributes);
    }

    protected function updateQuoteStateFromNewUser(): void
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $this->be($this->createUser(), 'api');

        $this->postJson(url('api/quotes/state'), $state);

        $this->be($this->user);
    }
}
