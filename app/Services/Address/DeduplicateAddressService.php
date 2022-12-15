<?php

namespace App\Services\Address;

use App\Contracts\LoggerAware;
use App\Models\Address;
use App\Models\Company;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DeduplicateAddressService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly AddressHashResolver $hashResolver,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function work(): void
    {
        $companies = Company::query()->has('addresses')->lazyById(10);

        $deletedDuplicates = Collection::empty();

        foreach ($companies as $company) {
            $addresses = $this->deduplicateAddressesOfCompany($company);

            $deletedDuplicates->push(...$addresses);
        }

        $this->logger->info('Deduplication completed.', [
            'total_duplicates_count' => $deletedDuplicates->count(),
        ]);
    }

    public function deduplicateAddressesOfCompany(Company $company): Collection
    {
        $addressPartitions = $this->mapAddressPartitionsOfCompany($company);

        if ($addressPartitions->isEmpty()) {
            return Collection::empty();
        }

        $duplicatesCount = $addressPartitions->sum(static function (BaseCollection $partition): int {
            return $partition[1]->count();
        });

        $this->logger->info("Found [$duplicatesCount] duplicates for the [{$company->getIdForHumans()}] company.", [
            'company_id' => $company->getKey(),
        ]);

        $logger = $this->logger;

        $deletedDuplicates = Collection::empty();

        $this->connectionResolver->connection()->transaction(
            static function () use ($addressPartitions, $company, $deletedDuplicates, $logger): void {
                $logger->debug("Deleting the duplicates...", [
                    'company_id' => $company->getKey(),
                ]);

                foreach ($addressPartitions as [$keep, $remove]) {
                    $company->addresses()->detach($remove);
                    $remove->each->delete();

                    $deletedDuplicates->push(...$remove);
                }
            }
        );

        return $deletedDuplicates;
    }

    /**
     * @param  Company  $company
     * @return BaseCollection
     */
    protected function mapAddressPartitionsOfCompany(Company $company): BaseCollection
    {
        $hashResolver = $this->hashResolver;

        return $company->addresses->lazy()
            // Hash the addresses using the standard hash resolver.
            ->groupBy(static function (Address $address) use ($hashResolver): string {
                return $hashResolver($address);
            })
            ->map(static function (BaseCollection $group) use ($company): Collection {
                return Collection::make($group->all())
                    ->loadExists([
                        'distributorQuotes',
                        'assets',
                        'companies' => static function (Builder $builder) use ($company): void {
                            $builder->whereKeyNot($company);
                        },
                    ]);
            })
            ->map(static function (BaseCollection $group): BaseCollection {
                // Partition the groups using quote/asset relation existence.
                /** @var Collection $keep */
                /** @var Collection $remove */
                [$keep, $remove] = $group->partition(static function (Address $address): bool {
                    return $address->distributor_quotes_exists || $address->assets_exists || $address->companies_exists;
                });

                // When addresses with the relations don't exist,
                // Put the most preferable address to keep which:
                // * Has Pipeliner reference
                // * Most recently updated
                $remove = $remove
                    ->sortByDesc('updated_at')
                    ->values();

                if ($keep->isNotEmpty() || $remove->isEmpty()) {
                    return collect([$keep->values(), $remove->values()]);
                }

                $addressIndexWithPlRef = $remove->lazy()
                    ->search(static function (Address $address) {
                        return $address->pl_reference !== null;
                    });

                if (false === $addressIndexWithPlRef) {
                    $keep->push($remove->shift());
                } else {
                    $keep->push($remove->pull($addressIndexWithPlRef));
                }

                return collect([$keep->values(), $remove->values()]);
            })
            // Reject the partitions which don't have addresses to keep.
            ->reject(static function (BaseCollection $partition): bool {
                return $partition[0]->isEmpty() || $partition[1]->isEmpty();
            })
            ->collect();
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}