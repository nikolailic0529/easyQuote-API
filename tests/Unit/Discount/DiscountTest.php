<?php

namespace Tests\Unit\Discount;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
    TruncatesDatabaseTables,
    WithFakeQuote
};
use Arr;

abstract class DiscountTest extends TestCase
{
    use DatabaseTransactions, TruncatesDatabaseTables, WithFakeUser, WithFakeQuote, AssertsListing;

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
        $response = $this->getJson(url("api/discounts/{$this->discountResource()}"), $this->authorizationHeader);

        $this->assertListing($response);
    }

    /**
     * Test Discount creating with valid attributes.
     *
     * @return void
     */
    public function testDiscountCreating()
    {
        $attributes = $this->makeGenericDiscountAttributes();

        $response = $this->postJson(url("api/discounts/{$this->discountResource()}"), $attributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure(array_keys(Arr::except($attributes, ['user_id'])));
    }

    /**
     * Test updating a newly created Discount with valid attributes.
     *
     * @return void
     */
    public function testDiscountUpdating()
    {
        $discount = $this->discountRepository()->create($this->makeGenericDiscountAttributes());

        $newAttributes = $this->makeGenericDiscountAttributes();

        $response = $this->patchJson(url("api/discounts/{$this->discountResource()}/{$discount->id}"), $newAttributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure(array_keys(Arr::except($newAttributes, ['user_id'])))
            ->assertJsonFragment(Arr::except($newAttributes, ['user_id']));
    }

    /**
     * Test deleting a newly created Discount.
     *
     * @return void
     */
    public function testDiscountDeleting()
    {
        $discount = $this->discountRepository()->create($this->makeGenericDiscountAttributes());

        $response = $this->deleteJson(url("api/discounts/{$this->discountResource()}/{$discount->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $discount->refresh();

        $this->assertNotNull($discount->deleted_at);
    }

    /**
     * Test activating a newly created Discount.
     *
     * @return void
     */
    public function testDiscountActivating()
    {
        $attributes = $this->makeGenericDiscountAttributes();

        $attributes = array_merge($attributes, ['country_id' => $this->quote->country_id, 'vendor_id' => $this->quote->vendor_id]);

        $discount = $this->discountRepository()->create($attributes);

        $response = $this->putJson(url("api/discounts/{$this->discountResource()}/activate/{$discount->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $discount->refresh();

        $this->assertNotNull($discount->activated_at);

        /**
         * Test availability at the acceptable discounts endpoint.
         */
        $response = $this->getJson(url("api/quotes/discounts/{$this->quote->id}"), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonFragment(['id' => $discount->id]);
    }

    /**
     * Test deactivating a newly created Discount.
     *
     * @return void
     */
    public function testDiscountDeactivating()
    {
        $discount = $this->discountRepository()->create($this->makeGenericDiscountAttributes());

        $response = $this->putJson(url("api/discounts/{$this->discountResource()}/deactivate/{$discount->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $discount->refresh();

        $this->assertNull($discount->activated_at);

        /**
         * Test no availability at the acceptable discounts endpoint.
         */
        $response = $this->getJson(url("api/quotes/discounts/{$this->quote->id}"), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonMissing(['id' => $discount->id]);
    }

    /**
     * Discount type in snake-case.
     *
     * @return string
     */
    abstract protected function discountResource(): string;

    /**
     * Repository implementation for a specified Discount type.
     *
     * @return void
     */
    abstract protected function discountRepository();

    /**
     * Generic Discount attributes specified for a type.
     *
     * @return array
     */
    abstract protected function makeGenericDiscountAttributes(): array;
}
