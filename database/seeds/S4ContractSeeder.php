<?php

use App\Models\{
    Customer\Customer,
    Data\Country
};
use Illuminate\Database\Seeder;

class S4ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customers = json_decode(file_get_contents(__DIR__ . '/models/customers.json'), true);

        $addresses = head(json_decode(file_get_contents(__DIR__ . '/models/customers_addresses.json'), true))['addresses'];
        $addresses = collect($addresses)->transform(function ($address) {
            $country_id = Country::code(data_get($address, 'country_code'))->firstOrFail()->id;
            data_set($address, 'country_id', $country_id);
            unset($address['country_code']);

            return $address;
        })->toArray();

        $contacts = head(json_decode(file_get_contents(__DIR__ . '/models/customers_contacts.json'), true))['contacts'];

        collect($customers)->each(function ($customer) use ($addresses, $contacts) {
            collect()->times(6)->each(function ($time) use ($customer, $addresses, $contacts) {
                $rfq = "CQ00" . mb_strtoupper(uniqid());

                $customer = Customer::create([
                    'name' => $customer['name'],
                    'rfq' => $rfq,
                    'valid_until' => now()->create($customer['valid_until'])->addDays(rand(101, 300))->toDateTimeString(),
                    'support_start' => now()->create($customer['support_start'])->addDays(rand(1, 100))->toDateTimeString(),
                    'support_end' => now()->create($customer['support_end'])->addDays(rand(101, 300))->toDateTimeString(),
                    'payment_terms' => $customer['payment_terms'],
                    'invoicing_terms' => $customer['invoicing_terms'],
                    'service_level' => $customer['service_level']
                ]);

                $customer->addresses()->createMany($addresses);
                $customer->contacts()->createMany($contacts);
            });
        });
    }
}
