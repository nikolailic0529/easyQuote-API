<?php

namespace Tests\Feature;

use App\Models\Customer\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 */
class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view customers listing.
     *
     * @return void
     */
    public function testCanViewCustomersListing()
    {
        $this->authenticateApi();

        factory(Customer::class, 30)->create();

        $this->getJson('api/quotes/customers')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'rfq',
                    'valid_until',
                    'support_start',
                    'support_end',
                    'created_at',
                ],
            ]);
    }

    /**
     * Test an ability to view a specified customer.
     *
     * @return void
     */
    public function testCanViewCustomer()
    {
        $this->authenticateApi();

        $customer = factory(Customer::class)->create();

        $this->getJson("api/quotes/customers/".$customer->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'rfq',
                'valid_until',
                'support_start',
                'support_end',
                'created_at',
            ]);
    }

    /**
     * Test an ability to delete a specified customer.
     *
     * @return void
     */
    public function testCanDeleteCustomer()
    {
        $this->authenticateApi();

        $customer = factory(Customer::class)->create();

        $this->deleteJson('api/quotes/customers/'.$customer->getKey())
            ->assertOk();

        $this->getJson('api/quotes/customers/'.$customer->getKey())
            ->assertNotFound();

    }
}
