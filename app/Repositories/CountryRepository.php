<?php

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Data\Country;

class CountryRepository implements CountryRepositoryInterface
{
    protected $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function all()
    {
        return cache()->sear('all-countries', function () {
            return $this->country->ordered()->get(['id', 'name']);
        });
    }

    public function findIdByCode($code)
    {
        if (is_array($code)) {
            $iso = implode(',', $code);
            return cache()->sear("country-id-iso:{$iso}", function () use ($code) {
                return $this->country->whereIn('iso_3166_2', $code)->pluck('id', 'iso_3166_2');
            });
        }

        throw_unless(is_string($code), new \InvalidArgumentException(
            sprintf('%s %s given.', INV_ARG_SA_01, gettype($code))
        ));

        return cache()->sear("country-id-iso:{$code}", function () use ($code) {
            return $this->country->where('iso_3166_2', $code)->value('id');
        });
    }
}
