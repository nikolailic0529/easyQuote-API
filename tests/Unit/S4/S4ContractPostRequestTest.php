<?php

namespace Tests\Unit\S4;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    WithClientCredentials,
};
use App\Models\Customer\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Str, Facades\DB};

/**
 * @group build
 */
class S4ContractPostRequestTest extends TestCase
{
    use WithFakeUser, WithClientCredentials, DatabaseTransactions;

    /**
     * Test Storing S4 Contract with valid attributes.
     *
     * @return void
     */
    public function testRequestWithValidAttributes(): void
    {

        $this->postJson('api/s4/quotes', [
            'quotation_valid_until' => now()->addYear()->format('m/d/Y'),
            'customer_name' => Str::random(),
            'country' => 'FR',
            'support_start_date' => now()->format('Y-m-d'),
            'support_end_date' => now()->addYear()->format('Y-m-d'),
            'service_levels' => [
                ['service_level' => 'Proactive Care 24x7']
            ],
            'addresses' => [
                [
                    "state" => null,
                    "post_code" => "92523",
                    "country_code" => "FR",
                    "address_type" => "Equipment",
                    "address_1" => "41-43 Rue De Villiers",
                    "address_2" => null,
                    "city" => "Neuilly Sur Seine",
                    "contact_name" => "Jean-Michel Perret",
                    "state_code" => null,
                    "contact_number" => "0080033140887464"
                ]
            ],
            'invoicing_terms' => 'UP_FRONT',
            'rfq_number' => Str::random(20),

        ], headers: $this->clientAuthHeader)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'quotation_valid_until',
                'customer_name',
                'country',
                'support_start_date',
                'support_end_date',
                'service_levels',
                'addresses',
                'invoicing_terms',
                'rfq_number',
            ]);
    }

    /**
     * Test Storing S4 Contract without Addresses.
     *
     * @return void
     */
    public function testRequestWithoutAddresses(): void
    {
        $contract = factory(Customer::class)->state('request')->raw();

        $this->postContract($contract)
            ->assertSuccessful()
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
        $contract = factory(Customer::class)->state('request')->raw(['rfq_number' => Str::random(40)]);

        $this->postContract($contract)->assertStatus(422);
    }

    /**
     * Test Storing S4 Contract with already existing RFQ Number.
     *
     * @return void
     */
    public function testRequestWithAlreadyExistingRfqNumber(): void
    {
        $rfq_number = DB::table('customers')->whereNull('deleted_at')->value('rfq');

        $contract = factory(Customer::class)->state('request')->raw(compact('rfq_number'));

        $this->postContract($contract)->assertStatus(422);
    }

    /**
     * Test Storing S4 Contract with the same RFQ number of a Contract which was deleted.
     *
     * @return void
     */
    public function testRequestWithSoftDeletedCustomer(): void
    {
        $customer = factory(Customer::class)->create();

        $customer->delete();

        $this->assertSoftDeleted($customer);

        $attributes = factory(Customer::class)->state('request')->raw(['rfq_number' => $customer->rfq]);

        $this->postContract($attributes)
            ->assertOk()
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
