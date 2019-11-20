<?php

use App\Models\Customer\Customer;
use Illuminate\Database\Seeder;

class CustomersAddressesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the addresses table
        Schema::disableForeignKeyConstraints();

        DB::table('addresses')->delete();

        Schema::enableForeignKeyConstraints();

        $addresses = json_decode(file_get_contents(__DIR__ . '/models/customers_addresses.json'), true);

        collect($addresses)->each(function ($address) {
            Customer::all()->each(function ($customer) use ($address) {
                collect($address['addresses'])->each(function ($address) use ($customer) {
                    $customer->addresses()->create($address);
                });
            });
        });
    }
}
