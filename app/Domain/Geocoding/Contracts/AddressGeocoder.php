<?php

namespace App\Domain\Geocoding\Contracts;

use App\Domain\Address\Models\Address;
use App\Domain\Location\Models\Location;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;

interface AddressGeocoder
{
    /**
     * Process locations for addresses without location relation.
     */
    public function geocodeAddressLocations(): void;

    /**
     * Perform update location relationship for provided address instance.
     * When location is not found (i.e. null) address won't be associated with any location.
     */
    public function performAddressGeocoding(Address $address): void;

    /**
     * Find location attributes in Google Geocoder API by address string.
     * If location is found, find existing location in database or create a new one.
     */
    public function findOrCreateLocationForAddress(string $address): ?Location;

    /**
     * Render polygon by provided north-east & south-west coordinates.
     */
    public function renderPolygon(float $neLat, float $neLng, float $swLat, float $swLng): Polygon;
}
