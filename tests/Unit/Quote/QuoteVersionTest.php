<?php

namespace Tests\Unit\Quote;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeQuote;
use Tests\Unit\Traits\WithFakeUser;
use Arr, Str;

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

        $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

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

    public function testVersionSet()
    {
        $this->updateQuoteStateFromNewUser();

        $nonUsingVersion = $this->quote->versionsSelection->firstWhere('is_using', '===', false);

        $version_id = $nonUsingVersion['id'];

        $response = $this->patchJson(url("api/quotes/version/{$this->quote->id}"), compact('version_id'), $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $actualUsingVersion = $this->quote->load('usingVersion')->usingVersion;

        $this->assertEquals($version_id, $actualUsingVersion->id);
    }

    public function testVersionUpdatingWithNewAttributes()
    {
        $user = $this->createUser();
        $authorizationHeader = $this->createAuthorizationHeaderForNewUser($user);

        $step1Response = $this->getJson(url('api/quotes/step/1'), $authorizationHeader);

        $company = Arr::random($step1Response->json('companies'));
        $vendor = Arr::random(Arr::get($company, 'vendors'));
        $country = Arr::random(Arr::get($vendor, 'countries'));

        $templatesParameters = [
            'company_id' => $company['id'],
            'vendor_id' => $vendor['id'],
            'country_id' => $country['id']
        ];

        $templatesResponse = $this->postJson(url('api/quotes/step/1'), $templatesParameters, $authorizationHeader);

        $template = Arr::random($templatesResponse->json());

        $closingDate = now()->addDays(rand(1, 10));

        $state = [
            'quote_id' => $this->quote->id,
            'quote_data' => [
                'company_id' => $company['id'],
                'vendor_id' => $vendor['id'],
                'country_id' => $country['id'],
                'quote_template_id' => $template['id'],
                'last_drafted_step' => 'Complete',
                'pricing_document' => Str::random(20),
                'service_agreement_id' => Str::random(20),
                'system_handle' => Str::random(20),
                'additional_details' => Str::random(2000),
                'additional_notes' => Str::random(2000),
                'closing_date' => $closingDate->format('Y-m-d'),
                'calculate_list_price' => true,
                'buy_price' => (float) rand(10000, 40000),
                'custom_discount' => (float) rand(5, 99),
            ],
            'margin' => [
                'quote_type' => 'Renewal',
                'method' => 'No Margin',
                'is_fixed' => false,
                'value' => (float) rand(1, 99)
            ]
        ];

        $response = $this->postJson(url('api/quotes/state'), $state, $authorizationHeader);

        $response->assertOk()
            ->assertExactJson(['id' => $this->quote->id]);

        $this->quote->usingVersion->refresh();

        $expectedQuoteAttributes = $state['quote_data'];
        $expectedQuoteAttributes['closing_date'] = $closingDate->format('d/m/Y');

        $assertableAttributes = array_keys($expectedQuoteAttributes);
        $actualQuoteAttributes = $this->quote->usingVersion->only($assertableAttributes);

        $this->assertEquals($actualQuoteAttributes, $expectedQuoteAttributes);

        $expectedMarginAttributes = $state['margin'];

        $actualMarginAttributes = $this->quote->usingVersion
            ->countryMargin->only(array_keys($expectedMarginAttributes));

        $this->assertEquals($actualMarginAttributes, $expectedMarginAttributes);
    }

    protected function createAuthorizationHeaderForNewUser(?User $user = null): array
    {
        $user = $user ?: $this->createUser();
        $token = $this->createAccessToken($user);

        return ['Authorization' => "Bearer {$token}"];
    }

    protected function updateQuoteStateFromNewUser(): void
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $authorizationHeader = $this->createAuthorizationHeaderForNewUser();

        $this->postJson(url('api/quotes/state'), $state, $authorizationHeader);
    }
}
