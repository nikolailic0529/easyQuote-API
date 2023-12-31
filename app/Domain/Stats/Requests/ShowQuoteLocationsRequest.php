<?php

namespace App\Domain\Stats\Requests;

use App\Domain\Geocoding\Contracts\AddressGeocoder;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;

class ShowQuoteLocationsRequest extends StatsAggregatorRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',

            'ne_lat' => 'required|numeric',
            'ne_lng' => 'required|numeric',

            'sw_lat' => 'required|numeric',
            'sw_lng' => 'required|numeric',
        ];
    }

    public function getPointOfCenter(): Point
    {
        return new Point(
            lat: $this->input('lat'),
            lng: $this->input('lng')
        );
    }

    public function getPolygon(): Polygon
    {
        return $this->container[AddressGeocoder::class]->renderPolygon(
            (float) $this->input('ne_lat'),
            (float) $this->input('ne_lng'),
            (float) $this->input('sw_lat'),
            (float) $this->input('sw_lng')
        );
    }
}
