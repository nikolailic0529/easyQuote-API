<?php

namespace Tests\Unit\S4;

use App\Models\Customer\Customer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Tests\Unit\Traits\WithClientCredentials;
use Str, DB;

class S4ContractPostRequestTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithClientCredentials;

    /**
     * Test Storing S4 Contract with valid attributes.
     *
     * @return void
     */
    public function testRequestWithValidAttributes(): void
    {
        $contract = factory(Customer::class, 'request')->state('addresses')->raw();
        $response = $this->postContract($contract);

        $response->assertSuccessful()
            ->assertJsonStructure(array_keys($contract));

        $this->assertCustomerExistsInDataBase($contract);
    }

    /**
     * Test Storing S4 Contract without Addresses.
     *
     * @return void
     */
    public function testRequestWithoutAddresses(): void
    {
        $contract = factory(Customer::class, 'request')->raw();

        $response = $this->postContract($contract);

        $response->assertSuccessful()
            ->assertJsonStructure(array_keys($contract));

        $this->assertCustomerExistsInDataBase($contract);
    }

    /**
     * Test Storing S4 Contract with invalid RFQ Number.
     *
     * @return void
     */
    public function testRequestWithInvalidRfqNumber(): void
    {
        $contract = factory(Customer::class, 'request')->raw(['rfq_number' => Str::random(40)]);

        $response = $this->postContract($contract);

        $response->assertStatus(422);
    }

    /**
     * Test Storing S4 Contract with already existing RFQ Number.
     *
     * @return void
     */
    public function testRequestWithAlreadyExistingRfqNumber(): void
    {
        $rfq_number = DB::table('customers')->whereNull('deleted_at')->value('rfq');

        $contract = factory(Customer::class, 'request')->raw(compact('rfq_number'));

        $response = $this->postContract($contract);

        $response->assertStatus(422);
    }

    /**
     * Test Storing S4 Contract with the same RFQ number of a Contract which was deleted.
     *
     * @return void
     */
    public function testRequestWithSoftDeletedCustomer(): void
    {
        $customer = tap(app('customer.repository')->random())->delete();

        $this->assertSoftDeleted($customer);

        $attributes = factory(Customer::class, 'request')->raw(['rfq_number' => $customer->rfq]);

        $response = $this->postContract($attributes);

        $response->assertOk()
            ->assertJsonStructure(array_keys($attributes));

        $this->assertCustomerExistsInDataBase($attributes);
    }

    protected function postContract(array $data)
    {
        return $this->postJson(url('/api/s4/quotes'), $data, $this->clientAuthHeader);
    }

    protected function assertCustomerExistsInDataBase(array $contract): void
    {
        $customerExistsInDatabase = DB::table('customers')
            ->whereNull('deleted_at')
            ->whereRfq($contract['rfq_number'])
            ->exists();

        $this->assertTrue($customerExistsInDatabase);
    }
}
