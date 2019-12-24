<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Arr;

class CustomerTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    protected $customerRepository;

    protected $customerFields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = app('customer.repository');

        $this->customerFields = ['id', 'name', 'rfq', 'valid_until', 'support_start', 'support_end', 'created_at'];
    }

    /**
     * Test Customers listing.
     *
     * @return void
     */
    public function testCustomerListing()
    {
        $response = $this->getJson(url('api/quotes/customers'), $this->authorizationHeader);

        $response->assertOk();

        $responseItemHasFields = Arr::has($response->json('0'), $this->customerFields);

        $this->assertTrue($responseItemHasFields);
    }

    /**
     * Test retrieving a specified Customer.
     *
     * @return void
     */
    public function testSpecifiedCustomerDisplaying()
    {
        $customer = $this->customerRepository->random();

        $response = $this->getJson(url("api/quotes/customers/{$customer->id}"), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure($this->customerFields);
    }
}
