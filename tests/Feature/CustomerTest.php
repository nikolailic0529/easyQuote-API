<?php

namespace Tests\Feature;

use App\Enum\CompanyType;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class CustomerTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

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

    /**
     * Test an ability to issue a new rescue customer number for a company
     * and create a new easyquote customer.
     *
     * @return void
     */
    public function testCanCreateEasyQuoteCustomerForCompany()
    {
        $this->authenticateApi();

        $internalCompany = factory(Company::class)->create([
            'type' => CompanyType::INTERNAL,
        ]);

        $response = $this->getJson('api/quotes/customers/number/'.$internalCompany->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'rfq_number',
            ]);

        $firstRfqNumber = $response->json('rfq_number');

        $response = $this->postJson('api/quotes/customers', [
            'int_company_id' => $internalCompany->getKey(),
            'addresses' => [
                factory(Address::class)->create()->getKey(),
            ],
            'contacts' => [
                factory(Contact::class)->create()->getKey(),
            ],
            'customer_name' => $this->faker->company,
            'email' => $this->faker->companyEmail,
            'quotation_valid_until' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'support_start_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'support_end_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'vat' => 'None',
            'invoicing_terms' => 'UP_FRONT',
            'service_levels' => [
                [
                    'service_level' => 'Foundation Care 24x7',
                ],
            ],
            'vendors' => [
                factory(Vendor::class)->create()->getKey(),
            ],
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'completeness',
                'calculate_list_price',
                'customer_id',
                'company_id',
                'id',
                'user_id',
                'activated_at',
                'updated_at',
                'created_at',
                'last_drafted_step',
                'has_group_description',
                'customer' => [
                    'id', 'name', 'rfq',
                ],
            ]);

        $this->assertSame($firstRfqNumber, $response->json('customer.rfq'));

        $response = $this->getJson('api/quotes/customers/number/'.$internalCompany->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'rfq_number',
            ]);

        $secondRfqNumber = $response->json('rfq_number');

        $this->assertSame((int)Str::afterLast($firstRfqNumber, '-') + 1, (int)Str::afterLast($secondRfqNumber, '-'));

        $response = $this->postJson('api/quotes/customers', [
            'int_company_id' => $internalCompany->getKey(),
            'addresses' => [
                factory(Address::class)->create()->getKey(),
            ],
            'contacts' => [
                factory(Contact::class)->create()->getKey(),
            ],
            'customer_name' => $this->faker->company,
            'email' => $this->faker->companyEmail,
            'quotation_valid_until' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'support_start_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'support_end_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'vat' => 'None',
            'invoicing_terms' => 'UP_FRONT',
            'service_levels' => [
                [
                    'service_level' => 'Foundation Care 24x7',
                ],
            ],
            'vendors' => [
                factory(Vendor::class)->create()->getKey(),
            ],
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'completeness',
                'calculate_list_price',
                'customer_id',
                'company_id',
                'id',
                'user_id',
                'activated_at',
                'updated_at',
                'created_at',
                'last_drafted_step',
                'has_group_description',
                'customer' => [
                    'id', 'name', 'rfq',
                ],
            ]);

        $this->assertSame($secondRfqNumber, $response->json('customer.rfq'));
    }
}
