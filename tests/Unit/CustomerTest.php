<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Support\Arr;

/**
 * @group build
 */
class CustomerTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    protected static $assertableAttributes = ['id', 'name', 'rfq', 'valid_until', 'support_start', 'support_end', 'created_at'];

    /**
     * Test Customers listing.
     *
     * @return void
     */
    public function testCustomerListing()
    {
        $response = $this->getJson(url('api/quotes/customers'))->assertOk();

        $this->assertTrue(Arr::has($response->json('0'), static::$assertableAttributes));
    }

    /**
     * Test retrieving a specified Customer.
     *
     * @return void
     */
    public function testSpecifiedCustomerDisplaying()
    {
        $customer = app('customer.repository')->random();

        $this->getJson(url("api/quotes/customers/{$customer->id}"))
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }
}
