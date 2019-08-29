<?php

namespace App\Repositories;

use App\Models\Country;
use App\Contracts\Repositories\CountryRepositoryInterface;

class CountryRepository implements CountryRepositoryInterface
{
    public function all()
    {
        return Country::ordered()->get();
    }
}