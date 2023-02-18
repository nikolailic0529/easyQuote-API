<?php

namespace App\Domain\Country\Controllers\V1;

use App\Domain\Country\Contracts\CountryRepositoryInterface;
use App\Domain\Country\Models\Country;
use App\Domain\Country\Requests\StoreCountryRequest;
use App\Domain\Country\Requests\UpdateCountryRequest;
use App\Domain\Country\Resources\V1\CountryCollection;
use App\Domain\Country\Resources\V1\CountryResource;
use App\Domain\Country\Resources\V1\{CountryList};
use App\Foundation\Http\Controller;

class CountryController extends Controller
{
    protected $countries;

    public function __construct(CountryRepositoryInterface $countries)
    {
        $this->countries = $countries;

        $this->authorizeResource(\App\Domain\Country\Models\Country::class, 'country');
    }

    public function __invoke()
    {
        return response()->json(
            CountryList::collection($this->countries->allCached())
        );
    }

    /**
     * Display a listing of the countries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->countries->search(request('search'))
            : $this->countries->paginate();

        return response()->json(CountryCollection::make($resource));
    }

    /**
     * Display the specified Country.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Country $country)
    {
        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Display a plain listing of Countries filtered by Vendor.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterCountriesByVendor(string $vendor)
    {
        return response()->json(
            $this->countries->findByVendor($vendor, ['id', 'iso_3166_2', 'name'])
        );
    }

    /**
     * Display a plain listing of Countries filtered by Company.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterCountriesByCompany(string $company)
    {
        return response()->json(
            $this->countries->findByCompany($company, ['id', 'iso_3166_2', 'name'])
        );
    }

    /**
     * Store a newly created Country in storage.
     *
     * @param \App\Domain\Country\Models\Country $country
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCountryRequest $request)
    {
        $country = $this->countries->create($request->validated());

        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Update the specified Country.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCountryRequest $request, Country $country)
    {
        $country = $this->countries->update($request->validated(), $country->id);

        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Remove the specified Country.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Country $country)
    {
        return response()->json(
            $this->countries->delete($country->id)
        );
    }

    /**
     * Activate the specified Country.
     *
     * @return \Illuminate\Http\Response
     */
    public function activate(Country $country)
    {
        $this->authorize('update', $country);

        return response()->json(
            $this->countries->activate($country->id)
        );
    }

    /**
     * Deactivate the specified Country.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Country $country)
    {
        $this->authorize('update', $country);

        return response()->json(
            $this->countries->deactivate($country->id)
        );
    }
}
