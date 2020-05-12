<?php

use App\Models\Customer\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customers = json_decode(file_get_contents(__DIR__ . '/models/customers.json'), true);

        DB::transaction(
            fn () =>
            collect($customers)->each(
                fn ($customer) => Customer::firstOrCreate(Arr::only($customer, 'rfq'), Arr::except($customer, 'country_code'))
            )
        );
    }
}
