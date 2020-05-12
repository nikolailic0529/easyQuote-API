<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class GeocoderData extends DataTransferObject
{
    public const RESULT_NOT_FOUND = 'result_not_found';

    public Point $coordinates;

    public float $lat;

    public float $lng;

    public ?string $place_id, $accuracy, $formatted_address, $country_code, $country_name, $postal_code, $postal_code_suffix, $postal_town, $administrative_area_level_1, $administrative_area_level_2, $route, $street_number, $locality;

    public static function create(array $data): GeocoderData
    {
        $lat = Arr::get($data, 'lat');
        $lng = Arr::get($data, 'lng');
        $coordinates = new Point($lat, $lng);

        $address_components = Collection::wrap(Arr::get($data, 'address_components'))->keyBy(fn ($component) => data_get($component, 'types.0'));

        $place_id = Arr::get($data, 'place_id');
        $accuracy = Arr::get($data, 'accuracy');
        $formatted_address = Arr::get($data, 'formatted_address');

        $country_code = data_get($address_components, 'country.short_name');
        $country_name = data_get($address_components, 'country.long_name');

        $administrative_area_level_1 = data_get($address_components, 'administrative_area_level_1.long_name');
        $administrative_area_level_2 = data_get($address_components, 'administrative_area_level_2.long_name');

        $postal_town = data_get($address_components, 'postal_town.long_name');
        $postal_code = data_get($address_components, 'postal_code.long_name');
        $postal_code_suffix = data_get($address_components, 'postal_code_suffix.long_name');

        $route = data_get($address_components, 'route.short_name');
        $street_number = data_get($address_components, 'street_number.short_name');
        $locality = data_get($address_components, 'locality.short_name');

        return new static(compact(
            'lat',
            'lng',
            'coordinates',
            'place_id',
            'accuracy',
            'formatted_address',
            'country_code',
            'country_name',
            'administrative_area_level_1',
            'administrative_area_level_2',
            'postal_town',
            'postal_code',
            'postal_code_suffix',
            'route',
            'street_number',
            'locality'
        ));
    }

    public function resultFound(): bool
    {
        return $this->accuracy !== static::RESULT_NOT_FOUND;
    }

    public function resultNotFound(): bool
    {
        return !$this->resultFound();
    }
}
