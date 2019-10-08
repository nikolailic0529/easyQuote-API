<?php namespace App\Repositories;

use App\Models\Data\Country;
use App\Contracts\Repositories\CountryRepositoryInterface;
use Cache;

class CountryRepository implements CountryRepositoryInterface
{
    protected $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function all()
    {
        return Cache::rememberForever('all-countries', function () {
            return $this->country->ordered()->get();
        });
    }
}
