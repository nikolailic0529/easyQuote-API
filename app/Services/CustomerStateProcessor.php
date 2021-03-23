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
                    'customer_name' => $data->customer_name,
                    'rfq_number' => $data->rfq_number,
                    'sequence_number' => $data->sequence_number,
                    'source' => Customer::EQ_SOURCE,
                    'service_levels' => $data->service_levels,
                    'quotation_valid_until' => $data->quotation_valid_until->toDateString(),
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
                    'customer_name' => $data->customer_name,
                    'rfq_number' => $data->rfq_number,
                    'sequence_number' => $data->sequence_number,
                    'source' => Customer::EQ_SOURCE,
                    'service_levels' => $data->service_levels,
                    'quotation_valid_until' => $data->quotation_valid_until->toDateString(),
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
                $customer->valid_until = $data->quotation_valid_until->toDateString();
                $customer->support_start = $data->support_start_date->toDateString();
                $customer->support_end = $data->support_end_date->toDateString();

                $customer->country()->associate(Country::where('iso_3166_2', $data->country_code)->firstOrFail());

                $customer->save();
            });

            $addressKeys = [];
            $contactKeys = [];

            foreach ($data->addresses as $address) {
                $addressKey = Address::where([
                    'address_type' => $address->address_type,
                    'address_1' => $address->address_1,
                    'address_2' => $address->address_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'post_code' => $address->post_code,
                    'contact_name' => $address->contact_name,
                    'contact_number' => $address->contact_number,
                    'contact_email' => $address->contact_email,
                ])->whereHas('country', function (Builder $builder) use ($address) {
                    $builder->where('iso_3166_2', $address->country_code);
                })->value('id');

                if (is_null($addressKey)) {
                    $address = tap(new Address($address->toArray()))->save();

                    $addressKey = $address->getKey();
                }

                array_push($addressKeys, $addressKey);

                $contactKey = Contact::where([
                    'phone' => $address->contact_phone,
                    'contact_name' => $address->contact_name,
                    'contact_type' => $address->address_type,
                    'email' => $address->contact_email,
                ])->value('id');

                if (is_null($contactKey)) {
                    $contact = tap(new Contact([
                        'phone' => $address->contact_phone,
                        'contact_name' => $address->contact_name,
                        'contact_type' => $address->address_type,
                        'first_name' => trim(Str::before($address->contact_name, ' ')),
                        'last_name' => trim(Str::after($address->contact_name, ' ')),
                        'email' => $address->contact_email,
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
