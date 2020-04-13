<?php

namespace Tests\Unit\Quote;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

class CustomerTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    /** @var array */
    protected static $assertableAttributes = ['id', 'name', 'rfq', 'valid_until', 'support_start', 'support_end'];

    /**
     * Test Customer listing.
     *
     * @return void
     */
    public function testCustomerListing()
    {
        $response = $this->getJson(url('api/quotes/customers'))->assertOk();

        $customer = head($response->json());

        $this->assertEmpty(array_diff_key(array_flip(static::$assertableAttributes), $customer));
    }

    /**
     * Test deleting a specified Customer.
     *
     * @return void
     */
    public function testCustomerDeleting()
    {
        $customer = app('customer.repository')->list()->random();

        $this->deleteJson(url("api/quotes/customers/{$customer->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($customer);
    }
}
