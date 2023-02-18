<?php

namespace App\Domain\Rescue\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyCategory;
use App\Domain\Contact\Services\ContactService;
use App\Domain\Rescue\Models\Customer;
use App\Foundation\Console\Concerns\WithProgress;
use App\Foundation\Console\Contracts\WithOutput;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CustomerFlowService implements \App\Domain\Rescue\Contracts\MigratesCustomerEntity, WithOutput
{
    use WithProgress;

    protected OutputInterface $output;

    #[Pure]
    public function __construct(protected ConnectionInterface $connection,
                                protected LoggerInterface $logger,
                                protected ContactService $contactService)
    {
        $this->output = new NullOutput();
    }

    public function migrateCustomers(): void
    {
        $migratingCustomerCursor = Customer::query()
            ->lazyById(100)
            ->filter(function (Customer $customer) {
                return is_null($customer->migrated_at);
            });

        $this->setProgressBar(new ProgressBar($this->output));

        foreach ($migratingCustomerCursor as $customer) {
            $this->migrateCustomer($customer);

            $this->advanceProgress();
        }

        $this->finishProgress();
    }

    public function migrateCustomer(Customer $customer): Company
    {
        return tap($this->performCustomerMigration($customer), function (Company $company) use ($customer) {
            with($customer, function (Customer $customer) use ($company) {
                $customer->migrated_at = now();
                $customer->referencedCompany()->associate($company);

                $this->connection->transaction(fn () => $customer->save());
            });

            $this->logger->info(CUS_M_01, [
                'customer_id' => $customer->getKey(),
                'customer_name' => $customer->name,
            ]);

            $this->advanceProgress();
        });
    }

    protected function performCustomerMigration(Customer $customer): Company
    {
        /** @var \App\Domain\Company\Models\Company $company */
        $company = Company::query()
            ->where('name', $customer->name)
            ->where('type', self::COMPANY_TYPE)
            ->firstOrNew();

        return tap($company, function () use ($customer, $company) {
            $emailFromCustomerAddresses = $customer->addresses()
                ->whereNotNull('contact_email')
                ->value('contact_email');

            $phoneNoFromCustomerAddresses = $customer->addresses()
                ->whereNotNull('contact_number')
                ->value('contact_number');

            $company->name = $customer->name;
            if ($company->owner === null) {
                $company->owner()->associate($customer->user_id);
            }
            $company->type = self::COMPANY_TYPE;
            $company->email ??= $customer->email ?? $emailFromCustomerAddresses;
            $company->vat ??= $customer->vat;
            $company->phone ??= $customer->phone ?? $phoneNoFromCustomerAddresses;

            if (false === $company->exists) {
                $customerQuoteData = $customer->quotes()->getQuery()
                    ->toBase()
                    ->select([
                        'country_id',
                        'vendor_id',
                        'quote_template_id',
                    ])
                    ->first();

                $countryKey = $customerQuoteData?->country_id ?? $customer->country_id;
                $vendorKey = $customerQuoteData?->vendor_id;
                $templateKey = $customerQuoteData?->quote_template_id;
                $company->source = $customer->source;

                $company->defaultCountry()->associate($countryKey);
                $company->defaultVendor()->associate($vendorKey);
                $company->defaultTemplate()->associate($templateKey);
            }

            $this->connection->transaction(fn () => $company->save());

            $addresses = static::rejectDuplicatedAddresses($company->addresses, $customer->addresses);

            $contacts = $customer->contacts;
            $contacts = $contacts->whenEmpty(fn () => $this->contactService->retrieveContactsFromAddresses($addresses));

            $categories = CompanyCategory::query()->where('name', self::COMPANY_CATEGORY)->get();

            $this->connection->transaction(function () use ($customer, $contacts, $addresses, $categories, $company) {
                $company->addresses()->syncWithoutDetaching($addresses);
                $company->contacts()->syncWithoutDetaching($contacts);
                $company->vendors()->syncWithoutDetaching($customer->vendors);
                $company->categories()->syncWithoutDetaching($categories);
            });
        });
    }

    protected static function rejectDuplicatedAddresses(Collection $companyAddresses, Collection $customerAddresses): Collection
    {
        return $customerAddresses->reject(
            fn (Address $address) => $companyAddresses
                ->contains(
                    fn (Address $companyAddress) => $companyAddress->address_type === $address->address_type
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

    public function setOutput(OutputInterface $output): static
    {
        return tap($this, function () use ($output) {
            $this->output = $output;
        });
    }
}
