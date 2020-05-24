<?php

namespace Tests\Unit\Quote;

use App\Models\Quote\Contract;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    AssertsListing,
    TruncatesDatabaseTables,
    WithFakeUser
};
use App\Models\Quote\Quote;

class ContractTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

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
        'is_version',
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
        'created_at'
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

        $contractTemplate = app('contract_template.repository')->random();

        $this->postJson(url('api/quotes/submitted/contract/' . $quote->id), ['contract_template_id' => $contractTemplate->id])
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test displaying a newly created Contract.
     *
     * @return void
     */
    public function testContractDisplaying()
    {
        $contract = $this->createFakeContract();

        $this->getJson(url('api/contracts/state/' . $contract->id))
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test reviewing a newly created Contract.
     *
     * @return void
     */
    public function testContractReview()
    {
        $contract = $this->createFakeContract();

        $this->getJson(url('api/contracts/state/review/' . $contract->id))
            ->assertOk()
            ->assertJsonStructure(['first_page', 'data_pages', 'last_page', 'payment_schedule']);
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

        $this->patchJson(url('api/contracts/state/' . $contract->id), $attributes)
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

        $this->postJson(url('api/contracts/drafted/submit/' . $contract->id))
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

        $this->postJson(url('api/contracts/submitted/unsubmit/' . $contract->id))
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

        $this->deleteJson(url('api/contracts/submitted/' . $contract->id))
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

        $this->deleteJson(url('api/contracts/drafted/' . $contract->id))
            ->assertOk()->assertExactJson([true]);

        $this->assertSoftDeleted($contract);
    }

    protected function createFakeContract(): Contract
    {
        $contractTemplate = app('contract_template.repository')->random();

        $attributes = ['contract_template_id' => $contractTemplate->id];

        return app('contract.state')->createFromQuote(factory(Quote::class)->create(), $attributes);
    }
}
