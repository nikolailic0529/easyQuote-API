<?php

namespace App\Http\Requests\Stats;

use App\Contracts\Services\LocationService;
use Grimzy\LaravelMysqlSpatial\{Types\Point, Types\Polygon,};

class ShowQuoteLocations extends StatsAggregatorRequest
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
            'sw_lng' => 'required|numeric'
        ];
    }

    public function getPointOfCenter(): Point
    {
        return $this->container[LocationService::class]->renderPoint(
            $this->input('lat'),
            $this->input('lng')
        );
    }

    public function getPolygon(): Polygon
    {
        return $this->container[LocationService::class]->renderPolygon(
            (float)$this->input('ne_lat'),
            (float)$this->input('ne_lng'),
            (float)$this->input('sw_lat'),
            (float)$this->input('sw_lng')
        );
    }
}
