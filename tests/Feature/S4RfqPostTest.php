<?php

namespace Tests\Feature;

use App\Models\Customer\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Arr, Carbon, Str};
use Tests\TestCase;
use function factory;
use function now;

/**
 * @group build
 * @group client-credentials
 * @group s4
 */
class S4RfqPostTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to post a new RFQ with valid data.
     *
     * @return void
     */
    public function testCanPostNewRfqWithValidData(): void
    {
        $this->authenticateAsClient();

        $response = $this->postJson('api/s4/quotes', $rfqData = [
            'quotation_valid_until' => now()->addYear()->format('m/d/Y'),
            'customer_name' => Str::random(),
            'country' => 'FR',
            'support_start_date' => now()->format('Y-m-d'),
            'support_end_date' => now()->addYear()->format('Y-m-d'),
            'service_levels' => [
                ['service_level' => 'Proactive Care 24x7'],
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
                    "contact_number" => "0080033140887464",
                ],
            ],
            'invoicing_terms' => 'UP_FRONT',
            'rfq_number' => Str::random(20),

        ])
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
        $this->assertSame(Carbon::parse($rfqData['support_start_date'])->format('m/d/Y'), $response->json('support_start_date'));
        $this->assertSame(Carbon::parse($rfqData['support_end_date'])->format('m/d/Y'), $response->json('support_end_date'));
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
     * Test an ability to post RFQ data without address data.
     *
     * @return void
     */
    public function testCanPostRfqWithoutAddressData(): void
    {
        $attributes = Arr::only(factory(Customer::class)->state('request')->raw(), [
            'customer_name',
            'rfq_number',
            'quotation_valid_until',
            'support_start_date',
            'support_end_date',
            'country',
            'service_levels',
            'invoicing_terms',
            'addresses',
        ]);
        $this->authenticateAsClient();

        $r = $this->postJson('/api/s4/quotes', $attributes)
            ->assertSuccessful()
            ->assertJsonStructure([
                'customer_name',
                'rfq_number',
                'quotation_valid_until',
                'support_start_date',
                'support_end_date',
                'country',
                'country_id',
                'service_levels',
                'invoicing_terms',
                'addresses' => [
                    '*' => [
                        'address_type',
                        'address_1',
                        'address_2',
                        'country_code',
                        'city',
                        'state',
                        'post_code',
                        'contact_name',
                        'contact_number',
                        'contact_email',
                    ]
                ],
            ]);

        $attributes['quotation_valid_until'] = Carbon::parse($attributes['quotation_valid_until'])->format('m/d/Y');
        $attributes['support_start_date'] = Carbon::parse($attributes['support_start_date'])->format('m/d/Y');
        $attributes['support_end_date'] = Carbon::parse($attributes['support_end_date'])->format('m/d/Y');

        foreach ($attributes as $attr => $value) {
            $r->assertJsonPath($attr, $value);
        }
    }

    /**
     * Test an ability to post RFQ data with invalid number.
     *
     * @return void
     */
    public function testCanNotPostRfqWithInvalidNumber(): void
    {
        $this->authenticateAsClient();

        $attributes = factory(Customer::class)->state('request')->raw(['rfq_number' => Str::random(40)]);

        $this->postJson('/api/s4/quotes', $attributes)
            ->assertStatus(422);
    }

    /**
     * Test an ability to post RFQ data with duplicated number.
     *
     * @return void
     */
    public function testCanNotPostRfqWithDuplicatedNumber(): void
    {
        $this->authenticateAsClient();

        $customer = factory(Customer::class)->create();

        $attributes = factory(Customer::class)->state('request')->raw([
            'rfq_number' => $customer->rfq,
        ]);

        $this->postJson('/api/s4/quotes', $attributes)
            ->assertStatus(422);
    }

    /**
     * Test an ability to post RFQ data with deleted number.
     *
     * @return void
     */
    public function testCanPostRfqForDeletedNumber(): void
    {
        $this->authenticateAsClient();

        $customer = factory(Customer::class)->create();

        $customer->delete();

        $this->assertSoftDeleted($customer);

        $attributes = Arr::only(factory(Customer::class)->state('request')->raw([
            'rfq_number' => $customer->rfq
        ]), [
            'customer_name',
            'rfq_number',
            'quotation_valid_until',
            'support_start_date',
            'support_end_date',
            'country',
            'service_levels',
            'invoicing_terms',
            'addresses',
        ]);

        $r = $this->postJson('/api/s4/quotes', $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'customer_name',
                'rfq_number',
                'quotation_valid_until',
                'support_start_date',
                'support_end_date',
                'country',
                'country_id',
                'service_levels',
                'invoicing_terms',
                'addresses' => [
                    '*' => [
                        'address_type',
                        'address_1',
                        'address_2',
                        'country_code',
                        'city',
                        'state',
                        'post_code',
                        'contact_name',
                        'contact_number',
                        'contact_email',
                    ]
                ],
            ]);

        $attributes['quotation_valid_until'] = Carbon::parse($attributes['quotation_valid_until'])->format('m/d/Y');
        $attributes['support_start_date'] = Carbon::parse($attributes['support_start_date'])->format('m/d/Y');
        $attributes['support_end_date'] = Carbon::parse($attributes['support_end_date'])->format('m/d/Y');

        foreach ($attributes as $attr => $value) {
            $r->assertJsonPath($attr, $value);
        }
    }
}
