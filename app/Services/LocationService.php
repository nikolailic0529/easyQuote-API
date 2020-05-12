<?php

namespace App\Services;

use App\Contracts\Repositories\CountryRepositoryInterface as Countries;
use App\Contracts\Services\LocationService as Contract;
use Spatie\Geocoder\Facades\Geocoder;
use App\DTO\GeocoderData;
use App\Models\{
    Address,
    Location
};
use App\Services\Concerns\WithProgress;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;

class LocationService implements Contract
{
    use WithProgress;

    protected Countries $countries;

    protected Address $address;

    protected Location $location;

    protected array $notFoundAddresses = [];

    public function __construct(Countries $countries, Address $address, Location $location)
    {
        $this->countries = $countries;   
        $this->address = $address;
        $this->location = $location;
    }

    /**
     * Process locations for addresses without location relation.
     *
     * @return void
     */
    public function updateAddressLocations(): void
    {
        $this->setProgressBar(head(func_get_args()), fn () => $this->address->whereNull('location_id')->count());

        /**
         * Begin cursor query for addresses where location_id is null
         */
        $this->address->on(MYSQL_UNBUFFERED)
            ->whereNull('location_id')
            ->cursor()->each(fn (Address $address) => $this->handleAddressLocation($address));

        $this->onUpdateAddressLocationsCompleted();
    }

    /**
     * Perform update location relationship for provided address instance.
     * When location is not found (i.e. null) address won't be associated with any location.
     *
     * @param Address $address
     * @return void
     */
    public function handleAddressLocation(Address $address): void
    {
         $location = $this->findOrCreateLocationForAddress(static::formatAddress($address));

         if ($location instanceof Location) {
             $address->location()->associate($location)->saveOrFail();

             report_logger(['message' => LC_AA_01], $address->toArray());
         }

         $this->advanceProgress();
    }

    /**
     * Find location attributes in Google Geocoder API by address string.
     * If location is found, find existing location in database or create a new one.
     *
     * @param string $address
     * @return Location|null
     */
    public function findOrCreateLocationForAddress(string $address): ?Location
    {
        if (($location = Location::whereSearchableAddress($address)->first()) instanceof Location) {
            report_logger(['message' => LC_FE_01], $location->toArray());

            return $location;
        }

        $result = Geocoder::getCoordinatesForAddress($address);

        $dto = GeocoderData::create($result);

        if ($dto->resultNotFound()) {
            $this->onLocationNotFound($address, $dto);
            
            return null;
        }

        $location = Location::wherePlaceId($dto->place_id)->first();

        if ($location instanceof Location) {
            report_logger(['message' => LC_FE_01], $location->toArray());
        
            return $location;
        }

        $countryId = $this->countries->findIdByCode($dto->country_code);

        return tap(Location::make($dto->toArray() + ['country_id' => $countryId, 'searchable_address' => $address]), function (Location $location) {
            $location->saveOrFail();

            report_logger(['message' => LC_FC_01], $location->toArray());
        });
    }

    public function renderPolygon(float $neLat, float $neLng, float $swLat, float $swLng): Polygon
    {
        return new Polygon([new LineString([
            // NW
            $this->renderPoint($neLat, $swLng),

            // NE
            $this->renderPoint($neLat, $neLng),

            // SE
            $this->renderPoint($swLat, $neLng),

            // SW
            $this->renderPoint($swLat, $swLng),

            // NW
            $this->renderPoint($neLat, $swLng),
        ])]);
    }

    public function renderPoint(float $lat, float $lng): Point
    {
        return new Point($lat, $lng);
    }

    /**
     * Action when address locations update is completed.
     *
     * @return void
     */
    protected function onUpdateAddressLocationsCompleted(): void
    {
        report_logger(['message' => LC_AUC_01], ['not_found_addresses' => $this->notFoundAddresses]);

        $this->finishProgress();
    }

    /**
     * Action when location not found.
     *
     * @param string $address
     * @param GeocoderData $result
     * @return void
     */
    protected function onLocationNotFound(string $address, GeocoderData $result): void
    {
        array_push($this->notFoundAddresses, compact('address', 'result'));

        report_logger(['ErrorCode' => 'LC_NF_01'], [LC_NF_01, $result->toArray()]);
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
}