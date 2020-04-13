<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeUser
};
use App\Models\Quote\Quote;
use App\Http\Resources\QuoteVersionResource;
use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;

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

    /**
     * Test versions updating with valid attributes.
     *
     * @return void
     */
    public function testVersionUpdatingWithNewAttributes()
    {
        $user = tap($this->createUser(), fn ($user) => $this->actingAs($user, 'api'));

        $state = transform(static::makeState(), fn ($state) =>
            Arr::set($state, 'quote_id', $this->quote->id)
        );

        $this->postJson(url('api/quotes/state'), $state)->assertOk()->assertExactJson(['id' => $this->quote->id]);

        Arr::set($state, 'quote_data.closing_date', Carbon::createFromFormat('Y-m-d', Arr::get($state, 'quote_data.closing_date'))->format('d/m/Y'));

        $resource = QuoteVersionResource::make($this->quote->refresh())->resolve();

        collect($state['quote_data'])->each(fn ($value, $attribute) => $this->assertEquals($value, Arr::get($resource, $attribute)));
        collect($state['margin'])->each(fn ($value, $attribute) => $this->assertEquals($value, Arr::get($resource, 'country_margin.'.$attribute)));
    }

    protected function updateQuoteStateFromNewUser(): void
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $this->be($this->createUser(), 'api');

        $this->postJson(url('api/quotes/state'), $state);

        $this->be($this->user);
    }

    protected static function makeState(): array
    {
        $quote = factory(Quote::class)->raw();
        $margin = factory(CountryMargin::class)->raw(Arr::only($quote, ['country_id', 'vendor_id']));

        return [
            'quote_data' => [
                'company_id'            => Arr::get($quote, 'company_id'),
                'vendor_id'             => Arr::get($quote, 'vendor_id'),
                'country_id'            => Arr::get($quote, 'country_id'),
                'quote_template_id'     => Arr::get($quote, 'quote_template_id'),
                'source_currency_id'    => Arr::get($quote, 'source_currency_id'),
                'target_currency_id'    => Arr::get($quote, 'target_currency_id'),
                'exchange_rate_margin'  => Arr::get($quote, 'exchange_rate_margin'),
                'last_drafted_step'     => Arr::get($quote, 'last_drafted_step'),
                'pricing_document'      => Arr::get($quote, 'pricing_document'),
                'service_agreement_id'  => Arr::get($quote, 'service_agreement_id'),
                'system_handle'         => Arr::get($quote, 'system_handle'),
                'additional_details'    => Arr::get($quote, 'additional_details'),
                'additional_notes'      => Arr::get($quote, 'additional_notes'),
                'closing_date'          => Arr::get($quote, 'closing_date'),
                'calculate_list_price'  => Arr::get($quote, 'calculate_list_price'),
                'buy_price'             => Arr::get($quote, 'buy_price'),
                'custom_discount'       => Arr::get($quote, 'custom_discount'),
            ],
            'margin' => $margin,
        ];
    }
}
