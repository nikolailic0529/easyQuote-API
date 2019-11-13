<?php

use App\Models\Customer\Customer;
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

        collect($customers)->each(function ($customer) {
            collect()->times(6)->each(function ($time) use ($customer) {
                $rfq = "CQ00" . mb_strtoupper(uniqid());
                DB::table('customers')->insert([
                    'id' => (string) Uuid::generate(4),
                    'name' => $customer['name'],
                    'rfq' => $rfq,
                    'valid_until' => now()->create($customer['valid_until'])->addDays(rand(101, 300))->toDateTimeString(),
                    'support_start' => now()->create($customer['support_start'])->addDays(rand(1, 100))->toDateTimeString(),
                    'support_end' => now()->create($customer['support_end'])->addDays(rand(101, 300))->toDateTimeString(),
                    'payment_terms' => $customer['payment_terms'],
                    'invoicing_terms' => $customer['invoicing_terms'],
                    'service_level' => $customer['service_level'],
                    'created_at' => now()->toDateTimeString()
                ]);
            });
        });

        $addresses = head(json_decode(file_get_contents(__DIR__ . '/models/customers_addresses.json'), true))['addresses'];
        $contacts = head(json_decode(file_get_contents(__DIR__ . '/models/customers_contacts.json'), true))['contacts'];

        Customer::doesntHave('quotes')->doesntHave('addresses')->doesntHave('contacts')->get()
            ->each(function ($customer) use ($addresses, $contacts) {
                $customer->addresses()->createMany($addresses);
                $customer->contacts()->createMany($contacts);
            });
    }
}