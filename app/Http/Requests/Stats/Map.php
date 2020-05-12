<?php

namespace App\Http\Requests\Stats;

use App\Contracts\Services\LocationService;
use Illuminate\Foundation\Http\FormRequest;
use Grimzy\LaravelMysqlSpatial\{
    Types\LineString,
    Types\Point,
    Types\Polygon,
};

class Map extends FormRequest
{
    protected LocationService $service;

    public function __construct(LocationService $service)
    {
        $this->service = $service;
    }

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

    public function centerPoint(): Point
    {
        return $this->service->renderPoint($this->lat, $this->lng);
    }

    public function polygon(): Polygon
    {
        return $this->service->renderPolygon(
            $this->ne_lat,
            $this->ne_lng,
            $this->sw_lat,
            $this->sw_lng
        );
    }
}
