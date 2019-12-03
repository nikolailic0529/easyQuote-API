<?php

use App\Models\{
    Contact,
    Customer\Customer
};
use Illuminate\Database\Seeder;

class CustomersContactsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the contacts table
        Schema::disableForeignKeyConstraints();

        DB::table('contacts')->delete();
        DB::table('contactables')->delete();

        Schema::enableForeignKeyConstraints();

        $contacts = head(json_decode(file_get_contents(__DIR__ . '/models/customers_contacts.json'), true))['contacts'];

        $contacts = collect($contacts)->keyBy('contact_type');
        $contacts = collect([
            Contact::type('Software')->firstOrCreate($contacts->get('Software'))->id,
            Contact::type('Hardware')->firstOrCreate($contacts->get('Hardware'))->id
        ]);

        Customer::all()->each(function ($customer) use ($contacts) {
            $customer->contacts()->sync($contacts);
        });
    }
}
