<?php

namespace App\Domain\Country\Controllers\V1;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Country\Queries\CountryQueries;
use App\Domain\Country\Requests\StoreCountryRequest;
use App\Domain\Country\Requests\UpdateCountryRequest;
use App\Domain\Country\Resources\V1\CountryCollection;
use App\Domain\Country\Resources\V1\CountryList;
use App\Domain\Country\Resources\V1\CountryResource;
use App\Domain\Country\Services\CountryEntityService;
use App\Domain\Vendor\Models\Vendor;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Country::class, 'country');
    }

    /**
     * List countries.
     */
    public function __invoke(CountryQueries $queries): JsonResponse
    {
        return response()->json(
            CountryList::collection($queries->listCountriesOrdered()->get())
        );
    }

    /**
     * Paginate countries.
     */
    public function index(Request $request, CountryQueries $queries): JsonResponse
    {
        $pagination = $queries->paginateCountriesQuery($request)->apiPaginate();

        return response()->json(CountryCollection::make($pagination));
    }

    /**
     * Show country.
     */
    public function show(Country $country): JsonResponse
    {
        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Filter countries by vendor.
     */
    public function filterCountriesByVendor(CountryQueries $queries, Vendor $vendor): JsonResponse
    {
        return response()->json(
            $queries->listCountriesByVendor($vendor)->get()
        );
    }

    /**
     * Filter countries by company.
     */
    public function filterCountriesByCompany(CountryQueries $queries, Company $company): JsonResponse
    {
        return response()->json(
            $queries->listCountriesByCompany($company)->get()
        );
    }

    /**
     * Create country.
     */
    public function store(
        StoreCountryRequest $request,
        CountryEntityService $service
    ): JsonResponse {
        $country = $service
            ->setCauser($request->user())
            ->createCountry($request->getCreateCountryData());

        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Update country.
     */
    public function update(
        UpdateCountryRequest $request,
        CountryEntityService $service,
        Country $country
    ): JsonResponse {
        $country = $service
            ->setCauser($request->user())
            ->updateCountry($country, $request->getUpdateCountryData());

        return response()->json(
            CountryResource::make($country)->load('defaultCurrency')
        );
    }

    /**
     * Delete country.
     */
    public function destroy(
        Request $request,
        CountryEntityService $service,
        Country $country
    ): Response {
        $service->setCauser($request->user())
            ->deleteCountry($country);

        return response()->noContent();
    }

    /**
     * Mark country active.
     *
     * @throws AuthorizationException
     */
    public function activate(
        Request $request,
        CountryEntityService $service,
        Country $country
    ): Response {
        $this->authorize('update', $country);

        $service->setCauser($request->user())
            ->markCountryAsActive($country);

        return response()->noContent();
    }

    /**
     * Mark country inactive.
     *
     * @throws AuthorizationException
     */
    public function deactivate(
        Request $request,
        CountryEntityService $service,
        Country $country
    ): Response {
        $this->authorize('update', $country);

        $service->setCauser($request->user())
            ->markCountryAsInactive($country);

        return response()->noContent();
    }
}
