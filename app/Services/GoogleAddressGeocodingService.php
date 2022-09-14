<?php

namespace App\Services;

use App\Contracts\LoggerAware;
use App\Contracts\Services\AddressGeocoder;
use App\DTO\GeocoderData;
use App\Models\{Address, Data\Country, Location};
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\Geocoder\Exceptions\CouldNotGeocode;
use Spatie\Geocoder\Geocoder;
use Throwable;

class GoogleAddressGeocodingService implements AddressGeocoder, LoggerAware
{
    protected LoggerInterface $logger;

    protected array $notFoundAddresses = [];

    public function __construct(protected Geocoder            $geocoder,
                                protected ConnectionInterface $connection,
                                protected Cache               $cache,
                                LoggerInterface               $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Process locations for addresses without location relation.
     *
     * @return void
     * @throws Throwable
     */
    public function geocodeAddressLocations(): void
    {
        if (!config('geocoder.enabled')) {
            return;
        }

        $cursor = Address::query()
            ->whereNull('location_id')
            ->lazyById();

        if ($cursor->isEmpty()) {
            $this->logger->info('No pending addresses to be geocoded.');

            return;
        }

        foreach ($cursor as $address) {
            $this->performAddressGeocoding($address);
        }

        $this->logger->info('Geocoding of address locations has been completed.', ['geocodeFailuresCount' => count($this->notFoundAddresses)]);
    }

    /**
     * Perform update location relationship for provided address instance.
     * When location is not found (i.e. null) address won't be associated with any location.
     *
     * @param Address $address
     * @return void
     * @throws Throwable
     */
    public function performAddressGeocoding(Address $address): void
    {
        $location = $this->findOrCreateLocationForAddress(self::formatAddress($address));

        if ($location instanceof Location) {
            $address->location()->associate($location);

            $this->connection->transaction(fn() => $address->save());

            $this->logger->info(LC_AA_01, [
                'address' => $address->only(['id', 'location_id']),
            ]);
        }
    }

    /**
     * Find location attributes in Google Geocoder API by address string.
     * If location is found, find existing location in database or create a new one.
     *
     * @param string $address
     * @return Location|null
     * @throws Throwable
     */
    public function findOrCreateLocationForAddress(string $address): ?Location
    {
        if ($this->notFoundResultExists($address)) {
            $this->onLocationNotFound($address);
        }

        /** @var Location|null $existingLocation */
        $existingLocation = Location::query()
            ->where('searchable_address', $address)
            ->first();

        if (false === is_null($existingLocation)) {
            $this->logger->info(LC_FE_01, [
                'location' => $existingLocation->only(['id', 'searchable_address']),
            ]);

            return $existingLocation;
        }

        try {
            $result = $this->geocoder->getCoordinatesForAddress($address);
        } catch (CouldNotGeocode $e) {
            return null;
        }

        $dto = GeocoderData::fromArray($result);

        if ($dto->resultNotFound()) {
            $this->onLocationNotFound($address);

            return null;
        }

        /** @var Location|null $location */
        $location = Location::query()
            ->where('place_id', $dto->place_id)
            ->first();

        if (false === is_null($location)) {
            $this->logger->info(LC_FE_01, [
                'location' => $location->only(['id', 'place_id']),
            ]);

            return $location;
        }

        $country = Country::query()
            ->where('iso_3166_2', $dto->country_code)
            ->first();

        return tap(new Location(), function (Location $location) use ($dto, $address, $country) {
            $location->fill($dto->toArray());

            $location->country()->associate($country);
            $location->searchable_address = $address;

            $this->connection->transaction(fn() => $location->save());

            $this->logger->info(LC_FC_01, [
                'location' => $location->only(['id', 'searchable_address']),
            ]);
        });
    }

    public function renderPolygon(float $neLat, float $neLng, float $swLat, float $swLng): Polygon
    {
        return new Polygon([new LineString([
            // NW
            new Point($neLat, $swLng),

            // NE
            new Point($neLat, $neLng),

            // SE
            new Point($swLat, $neLng),

            // SW
            new Point($swLat, $swLng),

            // NW
            new Point($neLat, $swLng),
        ])]);
    }

    protected function rememberNotFoundResult(string $address): void
    {
        $this->cache->forever(self::locationNotFoundCacheKey($address), true);
    }

    protected function notFoundResultExists(string $address): bool
    {
        return (bool)$this->cache->get(self::locationNotFoundCacheKey($address), false);
    }

    protected static function locationNotFoundCacheKey(string $address): string
    {
        return 'location.not_found:'.md5($address);
    }

    /**
     * Action when location not found.
     *
     * @param string $address
     * @return void
     */
    protected function onLocationNotFound(string $address): void
    {
        if (false === $this->notFoundResultExists($address)) {
            $this->rememberNotFoundResult($address);
        }

        array_push($this->notFoundAddresses, compact('address'));
    }

    /**
     * Represent address instance to plain address string.
     *
     * @param Address $address
     * @return string
     */
    protected static function formatAddress(Address $address): string
    {
        return implode(', ', array_filter($address->only('state', 'city', 'address_1', 'address_2', 'post_code')));
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}