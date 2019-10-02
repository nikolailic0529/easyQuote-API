<?php

use App\Models\Customer\Customer;
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

        Schema::enableForeignKeyConstraints();

        $contacts = json_decode(file_get_contents(__DIR__ . '/models/customers_contacts.json'), true);

        collect($contacts)->each(function ($contact) {
            Customer::where('rfq', $contact['rfq'])->get()->each(function ($customer) use ($contact) {
                collect($contact['contacts'])->each(function ($contact) use ($customer) {
                    $customer->contacts()->create($contact);
                });
            });
        });
    }
}
