<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\{CompanyRepositoryInterface as CompanyRepository,
    VendorRepositoryInterface as VendorRepository
};
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\{StoreCompanyRequest, UpdateCompanyContact, UpdateCompanyRequest};
use App\Http\Resources\{Company\Company as CompanyResource,
    Company\CompanyCollection,
    Company\ExternalCompanyList,
    Company\UpdatedCompany};
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Queries\CompanyQueries;
use App\Services\CompanyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    protected CompanyRepository $company;

    protected VendorRepository $vendor;

    public function __construct(CompanyRepository $company, VendorRepository $vendor)
    {
        $this->company = $company;
        $this->vendor = $vendor;
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the Companies.
     *
     * @return CompanyCollection
     */
    public function index(): CompanyCollection
    {
        $resource = request()->filled('search')
            ? $this->company->search(request('search'))
            : $this->company->all();

        return CompanyCollection::make($resource);
    }

    /**
     * Data for creating a new Company.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $vendors = $this->vendor->allFlatten();

        return response()->json(
            $this->company->data(compact('vendors'))
        );
    }

    /**
     * Display a listing of the existing external companies.
     *
     * @return JsonResponse
     */
    public function getExternal(): JsonResponse
    {
        return response()->json(
            $this->company->allExternal(['source' => Customer::EQ_SOURCE])
        );
    }

    /**
     * Paginate existing external companies.
     *
     * @param Request $request
     * @param CompanyQueries $queries
     * @return AnonymousResourceCollection
     */
    public function paginateExternalCompanies(Request $request, CompanyQueries $queries): AnonymousResourceCollection
    {
        $resource = $queries->paginateExternalCompaniesQuery($request)->apiPaginate();

        return ExternalCompanyList::collection(
            $resource
        );
    }

    /**
     * Display a listing of all internal companies.
     *
     * @return JsonResponse
     */
    public function getInternal(): JsonResponse
    {
        return response()->json(
            $this->company->allInternal(['id', 'name'])
        );
    }

    /**
     * Display a listing of companies with related countries.
     *
     * @return JsonResponse
     */
    public function showCompaniesWithCountries(): JsonResponse
    {
        return response()->json(
            $this->company->allInternalWithCountries(['id', 'name', 'short_code'])
        );
    }

    /**
     * Store a newly created Company in storage.
     *
     * @param StoreCompanyRequest $request
     * @param CompanyService $service
     * @return JsonResponse
     */
    public function store(StoreCompanyRequest $request, CompanyService $service): JsonResponse
    {
        $resource = $service->createCompany($request->getCreateCompanyData());

        return response()->json(
            UpdatedCompany::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified Company.
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json(
            UpdatedCompany::make($company)
        );
    }

    /**
     * Update the specified Company in storage.
     *
     * @param UpdateCompanyRequest $request
     * @param Company $company
     * @return JsonResponse
     */
    public function update(UpdateCompanyRequest $request, Company $company, CompanyService $service): JsonResponse
    {
        $resource = $service->updateCompany($company, $request->getUpdateCompanyData());

        return response()->json(
            UpdatedCompany::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Update a Contact of the Company.
     *
     * @param UpdateCompanyContact $request
     * @param Company $company
     * @param Contact $contact
     * @param CompanyService $service
     * @return JsonResponse
     * @throws \Throwable
     */
    public function updateCompanyContact(UpdateCompanyContact $request,
                                         Company $company,
                                         Contact $contact,
                                         CompanyService $service): JsonResponse
    {
        $this->authorize('update', $company);

        $result = $service->updateCompanyContact($company, $contact, $request->getUpdateContactData());

        return response()->json(
            $result,
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified Company from storage.
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function destroy(Company $company): JsonResponse
    {
        return response()->json(
            $this->company->delete($company->id)
        );
    }

    /**
     * Activate the specified Company from storage.
     *
     * @param Company $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        return response()->json(
            $this->company->activate($company->id)
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
     * @param Company $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        return response()->json(
            $this->company->deactivate($company->id)
        );
    }
}
