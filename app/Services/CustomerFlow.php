<?php

namespace App\Services;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface as Customers;
use App\Contracts\Services\CustomerFlow as Contract;
use App\Models\{
    Address,
    Company,
    Customer\Customer
};
use App\Services\Concerns\WithProgress;
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CustomerFlow implements Contract
{
    use WithProgress;

    protected const CUSTOMER_FLOW_ATTRIBUTES = ['type' => 'External', 'category' => 'End User'];

    protected Customer $customer;

    protected Customers $customers;

    protected Company $company;

    protected ContactService $contactService;

    public function __construct(Customer $customer, Company $company, Customers $customers, ContactService $contactService)
    {
        $this->customers = $customers;
        $this->customer = $customer;
        $this->company = $company;
        $this->contactService = $contactService;
    }

    public function migrateCustomers(): void
    {
        $query = $this->customer->on(MYSQL_UNBUFFERED)
            ->notMigrated()
            ->whereSource('S4')
            /** Select only the customers which are not having respective company. */
            ->where(fn (DbBuilder $query) => $query->selectRaw('COUNT(*)')->from('companies')
                ->where(static::CUSTOMER_FLOW_ATTRIBUTES)->whereColumn('customers.name', 'companies.name'), false);

        $this->setProgressBar(head(func_get_args()), fn () => $query->count());

        /**
         * Begin cursor query for specific customers.
         */
        $query
            ->cursor()
            ->each(fn (Customer $customer) => $this->migrateCustomer($customer));

        $this->finishProgress();
    }

    public function migrateCustomer(Customer $customer): Company
    {
        return tap($this->findOrCreateCustomerCompany($customer), function () use ($customer) {
            $customer->markAsMigrated();

            report_logger(['message' => CUS_M_01], $customer->toArray());

            $this->advanceProgress();
        });
    }

    protected function findOrCreateCustomerCompany(Customer $customer): Company
    {
        return DB::transaction(function () use ($customer) {
            /** @var Company */
            $company = $this->company->query()->firstOrNew([
                'name' => $customer->name,
                /** easyQuote customers need user_id to save a company for that user. */
                'user_id' => $customer->user_id
            ] + static::CUSTOMER_FLOW_ATTRIBUTES);

            $this->pullCustomerAttributes($company, $customer);

            /** Fill company attributes and save if company does not exist yet. */
            $this->pullCustomerAttributes($company, $customer);

            /** @var \Illuminate\Database\Eloquent\Collection */
            $addresses = static::rejectDuplicatedAddresses($company->addresses, $customer->addresses);

            /** @var \Illuminate\Database\Eloquent\Collection */
            $contacts = $customer->contacts;

            $contacts = $contacts->whenEmpty(fn () => $this->contactService->retrieveContactsFromAddresses($addresses));

            $company->addresses()->syncWithoutDetaching($addresses);
            $company->contacts()->syncWithoutDetaching($contacts);

            $company->vendors()->syncWithoutDetaching($customer->vendors);

            report_logger(['message' => CUS_ECAC_01]);

            return $company;
        });
    }

    protected static function rejectDuplicatedAddresses(Collection $companyAddresses, Collection $customerAddresses): Collection
    {
        return $customerAddresses->reject(
            fn (Address $address) => $companyAddresses
                ->contains(
                    fn (Address $companyAddress) =>
                    $companyAddress->address_type === $address->address_type
                        && $companyAddress->address_1 === $address->address_1
                        && $companyAddress->address_2 === $address->address_2
                        && $companyAddress->contact_name === $address->contact_name
                        && $companyAddress->contact_number === $address->contact_number
                        && $companyAddress->contact_email === $address->contact_email
                        && $companyAddress->city === $address->city
                        && $companyAddress->state === $address->state
                        && $companyAddress->state_code === $address->state_code
                        && $companyAddress->post_code === $address->post_code
                        && $companyAddress->country_id === $address->country_id
                )
        );
    }

    protected static function pullCustomerAttributes(Company $company, Customer $customer): void
    {
        if ($company->exists) {
            report_logger(['message' => CUS_ECE_01]);

            /** Fill company attributes from the customer instance when some of them don't exist in the Company instance. */
            $company->fill([
                'email' => $company->email ?? $customer->email,
                'vat'   => $company->vat ?? $customer->vat,
                'phone' => $company->phone ?? $customer->phone,
            ])->saveOrFail();

            return;
        }

        report_logger(['message' => CUS_ECNE_01]);

        /**
         * Retrieving the rest attributes from respective customer addresses and contacts.
         */
        $emails = $customer->addresses->pluck('contact_email')->filter();
        $numbers = $customer->addresses->pluck('contact_number')->filter();

        $quote = $customer->quotes()->first();

        $countryId = optional($quote)->country_id ?? $customer->country_id;
        $vendorId = optional($quote)->vendor_id;
        $templateId = optional($quote)->quote_template_id;

        $company->fill([
            'email'                 => $customer->email ?? $emails->first(),
            'phone'                 => $customer->phone ?? $numbers->first(),
            'vat'                   => $customer->vat,
            'default_country_id'    => $countryId,
            'default_vendor_id'     => $vendorId,
            'default_template_id'   => $templateId,
        ]);

        $company->saveOrFail();

        report_logger(['message' => CUS_ECS_01], $company->toArray());
    }
}
