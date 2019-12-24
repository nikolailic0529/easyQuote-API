<?php

namespace Tests\Unit\S4;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Str, DB;

class S4ContractPostRequestTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    /**
     * Test Storing S4 Contract with valid attributes.
     *
     * @return void
     */
    public function testRequestWithProperlyPostedData(): void
    {
        $contract = $this->makeGenericContract();
        $response = $this->postContract($contract);

        $response->assertSuccessful();
        $response->assertJsonStructure(array_keys($contract));

        $this->assertCustomerExistsInDataBase($contract);
    }

    /**
     * Test Storing S4 Contract without Addresses.
     *
     * @return void
     */
    public function testRequestWithoutAddresses(): void
    {
        $contract = $this->makeGenericContract();
        unset($contract['addresses']);

        $response = $this->postContract($contract);

        $response->assertSuccessful();
        $response->assertJsonStructure(array_keys($contract));

        $this->assertCustomerExistsInDataBase($contract);
    }

    /**
     * Test Storing S4 Contract with wrong RFQ Number.
     *
     * @return void
     */
    public function testRequestWithWrongRfqNumber(): void
    {
        $contract = $this->makeGenericContract();
        data_set($contract, 'rfq_number', Str::random(40));

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
        $contract = $this->makeGenericContract();
        $existingRfqNumber = DB::table('customers')->whereNull('deleted_at')->value('rfq');

        data_set($contract, 'rfq_number', $existingRfqNumber);

        $response = $this->postContract($contract);

        $response->assertStatus(422);
    }

    protected function postContract(array $data)
    {
        return $this->postJson(url('/api/s4/quotes'), $data, $this->authorizationHeader);
    }

    protected function assertCustomerExistsInDataBase(array $contract): void
    {
        $customerExistsInDatabase = DB::table('customers')->whereNull('deleted_at')->whereRfq($contract['rfq_number'])->exists();

        $this->assertTrue($customerExistsInDatabase);
    }

    protected function makeGenericContract()
    {
        return [
            'customer_name' => $this->faker->company,
            'rfq_number' => $this->faker->bankAccountNumber,
            'service_levels' => collect()->times(10)->map(function () {
                return ['service_level' => $this->faker->sentence];
            }),
            'quotation_valid_until' => $this->faker->date('m/d/Y'),
            'support_start_date' => $this->faker->date,
            'support_end_date' => $this->faker->date,
            'invoicing_terms' => $this->faker->sentence,
            'country' => $this->faker->countryCode,
            'addresses' => collect()->times(3)->map(function () {
                return [
                    'address_type' => collect(['Equipment', 'Software'])->random(),
                    'address_1' => $this->faker->address,
                    'address_2' => $this->faker->address,
                    'city' => $this->faker->city,
                    'state' => $this->faker->state,
                    'state_code' => $this->faker->stateAbbr,
                    'post_code' => $this->faker->postcode,
                    'country_code' => $this->faker->countryCode,
                    'contact_name' => $this->faker->firstName,
                    'contact_number' => $this->faker->phoneNumber
                ];
            })
        ];
    }
}
