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
    public function testCanStoreRfqWithValidAttributes(): void
    {
        $response = $this->postJson('api/s4/quotes', $rfqData = [
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

        $this->assertSame($rfqData['customer_name'], $response->json('customer_name'));
        $this->assertSame($rfqData['quotation_valid_until'], $response->json('quotation_valid_until'));
        $this->assertSame($rfqData['country'], $response->json('country'));
        $this->assertSame($rfqData['support_start_date'], $response->json('support_start_date'));
        $this->assertSame($rfqData['support_end_date'], $response->json('support_end_date'));
        $this->assertSame($rfqData['service_levels'], $response->json('service_levels'));

        $this->assertSame($rfqData['addresses'][0]['state'], $response->json('addresses.0.state'));
        $this->assertSame($rfqData['addresses'][0]['post_code'], $response->json('addresses.0.post_code'));
        $this->assertSame($rfqData['addresses'][0]['country_code'], $response->json('addresses.0.country_code'));
        $this->assertSame($rfqData['addresses'][0]['address_type'], $response->json('addresses.0.address_type'));
        $this->assertSame($rfqData['addresses'][0]['address_1'], $response->json('addresses.0.address_1'));
        $this->assertSame($rfqData['addresses'][0]['address_2'], $response->json('addresses.0.address_2'));
        $this->assertSame($rfqData['addresses'][0]['city'], $response->json('addresses.0.city'));
        $this->assertSame($rfqData['addresses'][0]['contact_name'], $response->json('addresses.0.contact_name'));
        $this->assertSame($rfqData['addresses'][0]['state_code'], $response->json('addresses.0.state_code'));
        $this->assertSame($rfqData['addresses'][0]['contact_number'], $response->json('addresses.0.contact_number'));

        $this->assertSame($rfqData['invoicing_terms'], $response->json('invoicing_terms'));
        $this->assertSame(strtoupper($rfqData['rfq_number']), $response->json('rfq_number'));
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
