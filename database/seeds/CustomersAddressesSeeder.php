<?php

use App\Models\{
    Address,
    Customer\Customer,
    Data\Country
};
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
        DB::table('addressables')->delete();

        Schema::enableForeignKeyConstraints();

        $addresses = head(json_decode(file_get_contents(__DIR__ . '/models/customers_addresses.json'), true))['addresses'];
        $addresses = collect($addresses)->transform(function ($address) {
            $country_id = Country::code(data_get($address, 'country_code'))->firstOrFail()->id;
            data_set($address, 'country_id', $country_id);
            unset($address['country_code']);

            return $address;
        })->keyBy('address_type');

        $addresses = collect([
            Address::type('Software')->firstOrCreate($addresses->get('Software'))->id,
            Address::type('Equipment')->firstOrCreate($addresses->get('Equipment'))->id
        ]);

        Customer::all()->each(function ($customer) use ($addresses) {
            $customer->addresses()->sync($addresses);
        });
    }
}
