<?php

namespace App\Contracts\Services;

use App\Models\Address;
use App\Models\Location;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;

interface LocationService
{
    /**
     * Process locations for addresses without location relation.
     *
     * @return void
     */
    public function updateAddressLocations(): void;

    /**
     * Perform update location relationship for provided address instance.
     * When location is not found (i.e. null) address won't be associated with any location.
     *
     * @param Address $address
     * @return void
     */
    public function handleAddressLocation(Address $address): void;

    /**
     * Find location attributes in Google Geocoder API by address string.
     * If location is found, find existing location in database or create a new one.
     *
     * @param string $address
     * @return Location|null
     */
    public function findOrCreateLocationForAddress(string $address): ?Location;

    /**
     * Render polygon by provided north-east & south-west coordinates.
     *
     * @param float $neLat
     * @param float $neLng
     * @param float $swLat
     * @param float $swLng
     * @return Polygon
     */
    public function renderPolygon(float $neLat, float $neLng, float $swLat, float $swLng): Polygon;

    /**
     * Render point by provided latitude & longitude.
     *
     * @param float $lat
     * @param float $lng
     * @return Point
     */
    public function renderPoint(float $lat, float $lng): Point;
}