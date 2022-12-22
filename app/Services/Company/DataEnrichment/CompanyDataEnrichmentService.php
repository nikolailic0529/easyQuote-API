<?php

namespace App\Services\Company\DataEnrichment;

use App\Contracts\LoggerAware;
use App\Enum\AddressType;
use App\Enum\CompanyStatusEnum;
use App\Events\Company\CompanyUpdated;
use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\Data\Country;
use App\Models\Industry;
use App\Services\Company\CompanyDataMapper;
use App\Services\Company\DataEnrichment\Exceptions\SourceResolvingException;
use App\Services\Company\DataEnrichment\Models\CompanyProfile;
use App\Services\Company\DataEnrichment\Sources\Source;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CompanyDataEnrichmentService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly CompanyDataMapper $dataMapper,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly SourceCollection $sources,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function work(callable $onStart = null, callable $onProgress = null): void
    {
        $countries = $this->getSupportedCountries();

        $query = Company::query()
            ->whereHas('addresses', static function (Builder $builder) use ($countries): void {
                $builder->where('address_type', AddressType::INVOICE)
                    ->whereHas('country', static function (Builder $builder) use ($countries): void {
                        $builder->whereIn('iso_3166_2', $countries);
                    });
            });

        if ($onStart) {
            $onStart($query->clone()->count());
        }

        foreach ($query->clone()->lazyById(100) as $company) {
            $this->enrichCompanyData($company);

            if ($onProgress) {
                $onProgress(true, $company);
            }
        }
    }

    protected function getSupportedCountries(): array
    {
        $countries = [];

        foreach ($this->sources as $source) {
            $countries[] = [...$countries, ...array_values($source->getSupportedCountries())];
        }

        return $countries;
    }

    public function enrichCompanyData(Company $company): void
    {
        try {
            $source = $this->resolveSourceForCompany($company);
        } catch (SourceResolvingException $e) {
            $this->logger->warning($e->getMessage());

            return;
        }

        $profile = $this->resolveCompanyProfile($company, $source);

        if ($profile) {
            $this->performCompanyUpdate($company, $profile);

            $this->logger->info("Company [$company->name] updated.", [
                'company_id' => $company->getKey(),
            ]);
        }
    }

    /**
     * @throws SourceResolvingException
     */
    protected function resolveSourceForCompany(Company $company): Source
    {
        /** @var Address|null $invoiceAddress */
        $invoiceAddress = $company->addresses
            ->lazy()
            ->sortByDesc('pivot.is_default')
            ->whereStrict('address_type', AddressType::INVOICE)
            ->first();

        if (null === $invoiceAddress) {
            throw SourceResolvingException::missingInvoiceAddress($company);
        }

        $countryCode = (string) $invoiceAddress->country?->iso_3166_2;

        foreach ($this->sources as $source) {
            if (in_array($countryCode, $source->getSupportedCountries(), true)) {
                return $source;
            }
        }

        throw SourceResolvingException::unsupportedCountry($countryCode);
    }

    protected function resolveCompanyProfile(Company $company, Source $source): ?CompanyProfile
    {
        if ($company->registered_number !== null) {
            $this->logger->info("Trying to match company using number [$company->registered_number].", [
                'company_id' => $company->getKey(),
            ]);

            $profile = $source->get($company->registered_number);

            if ($profile !== null) {
                return $profile;
            }

            $this->logger->warning("Could not match company profile using number [$company->registered_number].", [
                'company_id' => $company->getKey(),
            ]);
        }

        $aliases = collect([$company->name])->merge($company->aliases->pluck('name'))->unique()->values();

        $profile = null;

        foreach ($aliases as $name) {
            $this->logger->info("Trying to match company using name [$name].", [
                'company_id' => $company->getKey(),
            ]);

            $profile = $source->find($name);

            if ($profile !== null) {
                $this->logger->info("Company matched using name [$name].", [
                    'company_id' => $company->getKey(),
                ]);

                break;
            }
        }

        if (null === $profile) {
            $this->logger->warning("Could not match company profile using name.", [
                'company_id' => $company->getKey(),
            ]);
        }

        return $profile;
    }

    protected function performCompanyUpdate(Company $company, CompanyProfile $profile)
    {
        $oldCompany = $this->dataMapper->cloneCompany($company);

        $matchingRegisteredAddress = $company->addresses()
            ->where('address_type', AddressType::INVOICE)
            ->where('address_1', $profile->address->address1)
            ->where('post_code', $profile->address->postCode)
            ->first();

        $matchingRegisteredAddress ??= tap(new Address(), static function (Address $address) use ($profile): void {
            $address->address_type = AddressType::INVOICE;
            $address->address_1 = $profile->address->address1;
            $address->city = $profile->address->locality;
            $address->post_code = $profile->address->postCode;

            if (null !== $profile->address->country) {
                $address->country()->associate(
                    Country::query()
                        ->where('name', $profile->address->country)
                        ->first()
                );
            }
        });

        $industries = Industry::query()->whereIn('sic_code', $profile->sicCodes)->get();

        $company->creation_date = Carbon::instance($profile->creationDate);
        $company->registered_number = $profile->registeredNumber;
        $company->employees_number = $profile->employeesNumber;
        $company->status = match ($profile->status) {
            Enum\CompanyStatusEnum::Active => CompanyStatusEnum::Active,
            Enum\CompanyStatusEnum::Dissolved => CompanyStatusEnum::Dissolved,
            Enum\CompanyStatusEnum::Liquidation => CompanyStatusEnum::Liquidation,
        };

        $newAlias = null;

        if ($company->name !== $profile->name && $company->aliases()->where('name', $profile->name)->doesntExist()) {
            $newAlias = tap(new CompanyAlias(),
                static function (CompanyAlias $alias) use ($company, $profile): void {
                    $alias->name = $profile->name;
                    $alias->company()->associate($company);
                });
        }

        $this->connectionResolver->connection()
            ->transaction(static function () use ($company, $industries, $matchingRegisteredAddress, $newAlias): void {
                $company->save();
                $company->industries()->sync($industries);

                $matchingRegisteredAddress->save();

                $company->addresses()->syncWithoutDetaching($matchingRegisteredAddress);
                $newAlias?->save();
            });

        $this->eventDispatcher->dispatch(new CompanyUpdated(
            company: $company,
            oldCompany: $oldCompany,
        ));
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}