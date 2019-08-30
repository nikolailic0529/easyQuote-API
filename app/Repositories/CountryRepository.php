<?php

namespace App\Repositories;

use App\Models\Data\Country;
use App\Contracts\Repositories\CountryRepositoryInterface;

class CountryRepository implements CountryRepositoryInterface
{
    public function all()
    {
        return Country::ordered()->get();
    }
}