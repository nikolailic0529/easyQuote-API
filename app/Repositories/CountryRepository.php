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
}
