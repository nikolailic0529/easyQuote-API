<?php namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\CountryRepositoryInterface;

class CountriesController extends Controller
{
    protected $country;

    public function __construct(CountryRepositoryInterface $country)
    {
        $this->country = $country;
    }

    public function __invoke()
    {
        $countries = $this->country->all();
        return response()->json($countries);
    }
}
