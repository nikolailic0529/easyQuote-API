<?php

namespace App\Services;

use App\Contracts\Services\CustomerState;
use App\DTO\EQCustomer\EQCustomerData;
use App\DTO\S4\S4CustomerData;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Data\Country;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;

class CustomerStateProcessor implements CustomerState
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function createFromEqData(EQCustomerData $data): Customer
    {
        $this->connection->beginTransaction();

        try {
            $customer = tap(new Customer, function (Customer $customer) use ($data) {
                $customer->forceFill([
                    'int_company_id' => $data->int_company_id,
                    'name' => $data->customer_name,
                    'rfq' => $data->rfq_number,
                    'sequence_number' => $data->sequence_number,
                    'source' => Customer::EQ_SOURCE,
                    'service_levels' => $data->service_levels,
                    'valid_until' => $data->quotation_valid_until->toDateString(),
                    'support_start' => $data->support_start_date->toDateString(),
                    'support_end' => $data->support_end_date->toDateString(),
                    'invoicing_terms' => $data->invoicing_terms,
                    'vat' => $data->vat,
                    'email' => $data->email,
                    'phone' => $data->phone,
                ]);

                $customer->save();
            });

            $customer->addresses()->sync($data->address_keys);
            $customer->contacts()->sync($data->contact_keys);
            $customer->vendors()->sync($data->vendor_keys);

            $this->connection->commit();

            return $customer;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function updateFromEqData(Customer $customer, EQCustomerData $data): Customer
    {
        $this->connection->beginTransaction();

        try {
            tap($customer, function (Customer $customer) use ($data) {
                $customer->forceFill([
                    'int_company_id' => $data->int_company_id,
                    'name' => $data->customer_name,
                    'rfq' => $data->rfq_number,
                    'sequence_number' => $data->sequence_number,
                    'source' => Customer::EQ_SOURCE,
                    'service_levels' => $data->service_levels,
                    'valid_until' => $data->quotation_valid_until->toDateString(),
                    'support_start' => $data->support_start_date->toDateString(),
                    'support_end' => $data->support_end_date->toDateString(),
                    'invoicing_terms' => $data->invoicing_terms,
                    'vat' => $data->vat,
                    'email' => $data->email,
                    'phone' => $data->phone,
                ]);

                $customer->save();
            });

            $customer->addresses()->sync($data->address_keys);
            $customer->contacts()->sync($data->contact_keys);
            $customer->vendors()->sync($data->vendor_keys);

            $this->connection->commit();

            return $customer;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createFromS4Data(S4CustomerData $data): Customer
    {
        $this->connection->beginTransaction();

        try {
            $customer = tap(new Customer, function (Customer $customer) use ($data) {
                $customer->name = $data->customer_name;
                $customer->rfq = $data->rfq_number;
                $customer->service_levels = $data->service_levels;
                $customer->invoicing_terms = $data->invoicing_terms;
                $customer->valid_until = $data->quotation_valid_until->toDateString();
                $customer->support_start = $data->support_start_date->toDateString();
                $customer->support_end = $data->support_end_date->toDateString();
                $customer->source = Customer::S4_SOURCE;

                $customer->country()->associate(Country::where('iso_3166_2', $data->country_code)->firstOrFail());

                $customer->save();
            });

            $addressKeys = [];
            $contactKeys = [];

            foreach ($data->addresses as $addressData) {
                $addressKey = Address::query()->where([
                    'address_type' => $addressData->address_type,
                    'address_1' => $addressData->address_1,
                    'address_2' => $addressData->address_2,
                    'city' => $addressData->city,
                    'state' => $addressData->state,
                    'post_code' => $addressData->post_code,
                    'contact_name' => $addressData->contact_name,
                    'contact_number' => $addressData->contact_number,
                    'contact_email' => $addressData->contact_email,
                ])->whereHas('country', function (Builder $builder) use ($addressData) {
                    $builder->where('iso_3166_2', $addressData->country_code);
                })->value('id');

                if (is_null($addressKey)) {
                    $address = tap(new Address(), function (Address $address) use ($addressData) {

                        $address->address_type = $addressData->address_type;
                        $address->address_1 = $addressData->address_1;
                        $address->address_2 = $addressData->address_2;
                        $address->city = $addressData->city;
                        $address->state = $addressData->state;
                        $address->state_code = $addressData->state_code;
                        $address->post_code = $addressData->post_code;
                        $address->country()->associate(Country::query()->where('iso_3166_2', $addressData->country_code)->value('id'));
                        $address->contact_name = $addressData->contact_name;
                        $address->contact_number = $addressData->contact_number;
                        $address->contact_email = $addressData->contact_email;

                        $address->save();

                    });

                    $addressKey = $address->getKey();
                }

                array_push($addressKeys, $addressKey);

                $contactKey = Contact::query()->where([
                    'phone' => $addressData->contact_phone,
                    'contact_name' => $addressData->contact_name,
                    'contact_type' => $addressData->address_type,
                    'email' => $addressData->contact_email,
                ])->value('id');

                if (is_null($contactKey)) {
                    $contact = tap(new Contact([
                        'phone' => $addressData->contact_phone,
                        'contact_name' => $addressData->contact_name,
                        'contact_type' => $addressData->address_type,
                        'first_name' => trim(Str::before($addressData->contact_name, ' ')),
                        'last_name' => trim(Str::after($addressData->contact_name, ' ')),
                        'email' => $addressData->contact_email,
                        'is_verified' => true
                    ]))->save();

                    $contactKey = $contact->getKey();
                }

                array_push($contactKeys, $contactKey);
            }

            $customer->addresses()->sync($addressKeys);
            $customer->contacts()->sync($contactKeys);

            $this->connection->commit();

            return $customer;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function deleteCustomer(Customer $customer): void
    {
        $this->connection->beginTransaction();

        try {
            $customer->delete();

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
