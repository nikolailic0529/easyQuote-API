<?php

namespace Tests\Unit\Quote;

use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Resources\V1\QuoteVersionResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * @group build
 */
class RescueQuoteVersionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test a new Version creating when non-author user is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByNonAuthor()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $this->updateQuoteStateFromNewUser($quote);

        $versionsCount = $quote->versions()->count();
        $this->assertGreaterThan(0, $versionsCount);
    }

    /**
     * Test no Version creating when version author is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByAuthor()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $state = Quote::factory()->raw();
        $state['quote_id'] = $quote->id;

        $this->postJson(url('api/quotes/state'), $state);

        $versionsCount = $quote->versions()->count();
        $this->assertEquals(0, $versionsCount);
    }

    /**
     * Test Versions creating from different causers.
     *
     * @return void
     */
    public function testVersionCreatingFromDifferentCausers()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $expectedVersionsCount = 3;

        collect()->times($expectedVersionsCount)->each(fn () => $this->updateQuoteStateFromNewUser($quote));

        $actualVersionsCount = $quote->versions()->count();

        $this->assertEquals($expectedVersionsCount, $actualVersionsCount);
    }

    /**
     * Test assigning a newly created Quote Version.
     *
     * @return void
     */
    public function testVersionSet()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $this->updateQuoteStateFromNewUser($quote);

        $inactiveVersion = $quote->refresh()->versionsSelection->firstWhere('is_using', '===', false);

        $response = $this->patchJson(url("api/quotes/version/{$quote->id}"), ['version_id' => $inactiveVersion['id']]);

        $response->assertOk()->assertExactJson([true]);

        $actualActiveVersion = $quote->refresh()->activeVersionOrCurrent;

        $this->assertEquals($inactiveVersion['id'], $actualActiveVersion->getKey());
    }

    /**
     * Test deleting a newly created Quote Version.
     *
     * @return void
     */
    public function testVersionDeleting()
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $this->updateQuoteStateFromNewUser($quote);

        $version = $quote->versions()->first();

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
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        $this->authenticateApi();

        $state = transform(static::makeState(), fn ($state) => Arr::set($state, 'quote_id', $quote->id));

        $this->postJson(url('api/quotes/state'), $state)
//            ->dump()
            ->assertOk()
            ->assertExactJson(['id' => $quote->id]);

        Arr::set($state, 'quote_data.closing_date', Carbon::createFromFormat('Y-m-d', Arr::get($state, 'quote_data.closing_date'))->format('d/m/Y'));

        $resource = QuoteVersionResource::make($quote->refresh())->resolve();

        foreach (Arr::except(Arr::dot($state['quote_data']), 'additional_notes') as $attribute => $value) {
            $this->assertEquals($value, Arr::get($resource, $attribute), $attribute);
        }

        foreach (Arr::dot($state['margin']) as $attribute => $value) {
            $this->assertEquals($value, Arr::get($resource, 'country_margin.'.$attribute), $attribute);
        }
    }

    protected function updateQuoteStateFromNewUser(Quote $quote): void
    {
        $state = Quote::factory()->raw();
        $state['quote_id'] = $quote->id;

        $originalUser = $this->app['auth']->user();

        $this->authenticateApi();

        $this->postJson(url('api/quotes/state'), $state);

        $this->authenticateApi($originalUser);
    }

    protected static function makeState(): array
    {
        $quote = factory(Quote::class)->raw();
        $margin = factory(CountryMargin::class)->raw(Arr::only($quote, ['country_id', 'vendor_id']));

        return [
            'quote_data' => [
                'company_id' => Arr::get($quote, 'company_id'),
                'vendor_id' => Arr::get($quote, 'vendor_id'),
                'country_id' => Arr::get($quote, 'country_id'),
                'quote_template_id' => Arr::get($quote, 'quote_template_id'),
                'source_currency_id' => Arr::get($quote, 'source_currency_id'),
                'target_currency_id' => Arr::get($quote, 'target_currency_id'),
                'exchange_rate_margin' => Arr::get($quote, 'exchange_rate_margin'),
                'last_drafted_step' => Arr::get($quote, 'last_drafted_step'),
                'pricing_document' => Arr::get($quote, 'pricing_document'),
                'service_agreement_id' => Arr::get($quote, 'service_agreement_id'),
                'system_handle' => Arr::get($quote, 'system_handle'),
                'additional_details' => Arr::get($quote, 'additional_details'),
                'additional_notes' => Arr::get($quote, 'additional_notes'),
                'closing_date' => Arr::get($quote, 'closing_date'),
                'calculate_list_price' => Arr::get($quote, 'calculate_list_price'),
                'buy_price' => Arr::get($quote, 'buy_price'),
                'custom_discount' => Arr::get($quote, 'custom_discount'),
            ],
            'margin' => $margin,
        ];
    }
}
