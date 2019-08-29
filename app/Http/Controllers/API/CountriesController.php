<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Country;

class CountriesController extends Controller
{
    protected $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function __invoke()
    {
        $countries = $this->country->ordered()->get();
        return response()->json($countries);
    }
}