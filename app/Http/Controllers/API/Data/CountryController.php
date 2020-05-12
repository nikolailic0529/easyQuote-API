<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Http\Requests\Country\{
    StoreCountryRequest,
    UpdateCountryRequest
};
use App\Http\Resources\Country\{
    CountryCollection,
    CountryList,
    CountryResource
};
use App\Models\Data\Country;

class CountryController extends Controller
{
    protected $countries;

    public function __construct(CountryRepositoryInterface $countries)
    {
        $this->countries = $countries;

        $this->authorizeResource(Country::class, 'country');
    }

    public function __invoke()
    {
        return response()->json(
          CountryList::collection($this->countries->allCached())
        );
    }

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
     * @param \App\Models\Data\Country $country
     * @return \Illuminate\Http\Response
     */
    public function show(Country $country)
    {
        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Store a newly created Country in storage.
     *
     * @param  \App\Models\Data\Country $country
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
     * @param  \App\Models\Data\Country $country
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
     * @param  \App\Models\Data\Country $country
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
     * @param  \App\Models\Data\Country $country
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
     * @param  \App\Models\Data\Country $country
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
