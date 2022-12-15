<?php

namespace App\Services\Address;

use App\Contracts\LoggerAware;
use App\Events\Address\AddressUpdated;
use App\Integrations\Google\AddressValidation\Enum\RegionCodeEnum;
use App\Integrations\Google\AddressValidation\Exceptions\AddressValidationException;
use App\Integrations\Google\AddressValidation\Models\ValidateAddressRequest;
use App\Integrations\Google\AddressValidation\Models\ValidateAddressRequestAddress;
use App\Integrations\Google\AddressValidation\Models\ValidationResultAddressPostalAddress;
use App\Integrations\Google\AddressValidation\ValidatesAddress;
use App\Models\Address;
use App\Models\Data\Country;
use App\Models\State;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ValidateAddressService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly ValidatesAddress $integration,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly Cache $cache,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function work(callable $onStart = null, callable $onProgress = null): void
    {
        $this->logger->debug('Address validation: starting...');

        $query = Address::query()
            ->where(static function (Builder $builder): void {
                $builder->whereNull('validated_at')
                    ->orWhereColumn('validated_at', '<', 'updated_at');
            });

        if ($onStart) {
            $onStart($query->count());
        }

        $changedCount = 0;

        foreach ($query->lazyById() as $address) {
            $result = $this->validateAddress($address);

            if ($onProgress) {
                $onProgress($result, $address);
            }

            $changedCount += (int) $result;
        }

        $this->logger->debug('Address validation: completed.', [
            'changed_count' => $changedCount,
        ]);
    }

    /**
     * @throws AddressValidationException
     */
    public function validateAddress(Address $address): bool
    {
        $addressLines = collect([$address->address_1, $address->address_2])->filter()->values();

        if ($addressLines->isEmpty()) {
            $this->logger->warning('Address validation: empty address lines.', [
                'address_id' => $address->getKey(),
            ]);

            return false;
        }

        try {
            $response = $this->integration->validateAddress(
                new ValidateAddressRequest(
                    new ValidateAddressRequestAddress(
                        addressLines: $addressLines->all(),
                        administrativeArea: $address->state_code ?? $address->state,
                        postalCode: $address->post_code,
                        languageCode: 'en',
                    )
                )
            );
        } catch (AddressValidationException $e) {
            if ($e->getCode() === 403) {
                throw $e;
            }

            $this->logger->warning($e->getMessage(), [
                'address_id' => $address->getKey(),
            ]);

            return false;
        }

        // Reject incomplete results
        if (!$response->result->verdict->addressComplete) {
            $this->logger->warning('Address validation: verdict - address incomplete.', [
                'address_id' => $address->getKey(),
                'response_id' => $response->responseId,
            ]);

            return false;
        }

        $this->performAddressUpdate($address, $response->result->address->postalAddress);

        $this->logger->debug('Address validation: address updated.', [
            'changes' => $address->getChanges(),
            'address_id' => $address->getKey(),
            'response_id' => $response->responseId,
        ]);

        return true;
    }

    protected function performAddressUpdate(Address $address, ValidationResultAddressPostalAddress $postalAddress): void
    {
        $oldAddress = (new Address())->setRawAttributes($address->getRawOriginal());

        [$address->address_1, $address->address_2] = ($postalAddress->addressLines ?? []) + [$address->address_1, null];

        $address->city = $postalAddress->locality;
        $address->post_code = $postalAddress->postalCode;

        $stateName = $this->resolveStateNameFromAdministrativeArea(
            $postalAddress->administrativeArea,
            $postalAddress->regionCode
        );

        $address->state = $stateName
            ?? $postalAddress->administrativeArea
            ?? $address->state;

        if ($this->regionNeedsStateCode($postalAddress->regionCode)) {
            $address->state_code = $postalAddress->administrativeArea;
        } else {
            $address->state_code = null;
        }

        if ($postalAddress->regionCode) {
            $address->country()->associate(
                $this->resolveCountryFromRegionCode($postalAddress->regionCode)
            );
        }

        $address->validated_at = $address->freshTimestamp();

        $this->connectionResolver->connection()->transaction(static function () use ($address): void {
            $address->save();
        });

        $this->eventDispatcher->dispatch(new AddressUpdated($oldAddress, $address));
    }

    protected function regionNeedsStateCode(?RegionCodeEnum $regionCode): bool
    {
        if (RegionCodeEnum::IE === $regionCode) {
            return false;
        }

        return true;
    }

    protected function resolveCountryFromRegionCode(RegionCodeEnum $regionCode): ?Country
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Country::query()->where('iso_3166_2', $regionCode)->first();
    }

    protected function resolveStateNameFromAdministrativeArea(?string $area, ?RegionCodeEnum $regionCode): ?string
    {
        if (null === $regionCode || null === $area) {
            return $area;
        }

        /** @var State|null $state */
        $state = State::query()
            ->whereRelation('country', 'iso_3166_2', '=', $regionCode)
            ->where('state_code', $area)
            ->first();

        return $state?->name;
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}