<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Data\Country;
use Illuminate\Console\Command;

class CustomersUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:customers-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the S4 Customers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $addresses = head(json_decode(file_get_contents(database_path('seeders/models/customers_addresses.json')), true))['addresses'];
        $addresses = collect($addresses)->transform(function ($address) {
            $country_id = Country::code(data_get($address, 'country_code'))->firstOrFail()->id;
            data_set($address, 'country_id', $country_id);
            unset($address['country_code']);

            return $address;
        })->keyBy('address_type');

        $contacts = head(json_decode(file_get_contents(database_path('seeders/models/customers_contacts.json')), true))['contacts'];
        $contacts = collect($contacts)->keyBy('contact_type');

        $addresses = collect([
            Address::type('Software')->firstOrCreate($addresses->get('Software'))->id,
            Address::type('Equipment')->firstOrCreate($addresses->get('Equipment'))->id
        ]);

        $contacts = collect([
            Contact::type('Software')->firstOrCreate($contacts->get('Software'))->id,
            Contact::type('Hardware')->firstOrCreate($contacts->get('Hardware'))->id
        ]);

        Customer::doesntHave('addresses')->get()->each(function ($customer) use ($addresses) {
            $customer->addresses()->sync($addresses);
            $this->output->write('.');
        });

        Customer::doesntHave('contacts')->get()->each(function ($customer) use ($contacts) {
            $customer->contacts()->sync($contacts);
            $this->output->write('.');
        });
    }
}
