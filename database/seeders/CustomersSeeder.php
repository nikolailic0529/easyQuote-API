<?php

namespace Database\Seeders;

use App\Domain\Rescue\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run()
    {
        $customers = json_decode(file_get_contents(__DIR__.'/models/customers.json'), true);

        DB::transaction(
            fn () => collect($customers)->each(
                fn ($customer) => Customer::query()->firstOrCreate(Arr::only($customer, 'rfq'), Arr::except($customer, 'country_code'))
            )
        );
    }
}
