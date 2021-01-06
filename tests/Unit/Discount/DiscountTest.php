<?php

namespace Tests\Unit\Discount;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
    WithFakeQuote
};
use Illuminate\Support\Arr;

abstract class DiscountTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, AssertsListing, DatabaseTransactions;

    protected $truncatableTables = [
        'multi_year_discounts', 'sn_discounts', 'pre_pay_discounts', 'promotional_discounts'
    ];

    /**
     * Test Discount listing.
     *
     * @return void
     */
    public function testDiscountListing()
    {
        $response = $this->getJson(url("api/discounts/{$this->resource()}"));

        $this->assertListing($response);
    }

    /**
     * Test Discount creating with valid attributes.
     *
     * @return void
     */
    public function testDiscountCreating()
    {
        $attributes = factory($this->model())->raw();

        $this->postJson(url("api/discounts/{$this->resource()}"), $attributes)
            ->assertOk()
            ->assertJsonStructure(array_keys($attributes));
    }

    /**
     * Test updating a newly created Discount with valid attributes.
     *
     * @return void
     */
    public function testDiscountUpdating()
    {
        $discount = factory($this->model())->create();

        $attributes = factory($this->model())->raw();

        $this->patchJson(url("api/discounts/{$this->resource()}/{$discount->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure(array_keys(Arr::except($attributes, ['user_id'])))
            ->assertJsonFragment(Arr::except($attributes, ['user_id']));
    }

    /**
     * Test deleting a newly created Discount.
     *
     * @return void
     */
    public function testDiscountDeleting()
    {
        $discount = factory($this->model())->create();

        $this->deleteJson(url("api/discounts/{$this->resource()}/{$discount->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($discount);
    }

    /**
     * Test activating a newly created Discount.
     *
     * @return void
     */
    public function testDiscountActivating()
    {
        $quote = $this->createQuote($this->user);

        $attributes = ['country_id' => $quote->country_id, 'vendor_id' => $quote->vendor_id];
        $discount = tap(factory($this->model())->create($attributes))->deactivate();

        $this->putJson(url("api/discounts/{$this->resource()}/activate/{$discount->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($discount->refresh()->activated_at);

        /**
         * Test availability at the acceptable discounts endpoint.
         */
        $this->getJson(url("api/quotes/discounts/{$quote->id}"))
            ->assertOk()
            ->assertJsonFragment(['id' => $discount->id]);
    }

    /**
     * Test deactivating a newly created Discount.
     *
     * @return void
     */
    public function testDiscountDeactivating()
    {
        $quote = $this->createQuote($this->user);
        
        $discount = factory($this->model())->create();

        $this->putJson(url("api/discounts/{$this->resource()}/deactivate/{$discount->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($discount->refresh()->activated_at);

        /**
         * Test no availability at the acceptable discounts endpoint.
         */
        $this->getJson(url("api/quotes/discounts/{$quote->id}"))
            ->assertOk()
            ->assertJsonMissing(['id' => $discount->id]);
    }

    /**
     * Resource type in snake-case.
     *
     * @return string
     */
    abstract protected function resource(): string;

    /**
     * Model class.
     *
     * @return string
     */
    abstract protected function model(): string;
}
