<?php namespace App\Repositories;

use App\Models\Data\Country;
use App\Contracts\Repositories\CountryRepositoryInterface;

class CountryRepository implements CountryRepositoryInterface
{
    protected $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function all()
    {
        return $this->country->ordered()->get();
    }
}
