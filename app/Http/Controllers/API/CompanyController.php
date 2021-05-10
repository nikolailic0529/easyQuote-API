<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\{CompanyRepositoryInterface as CompanyRepository,
    VendorRepositoryInterface as VendorRepository};
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\{PaginateCompanies, StoreCompanyRequest, UpdateCompanyContact, UpdateCompanyRequest};
use App\Http\Resources\{Company\CompanyCollection, Company\ExternalCompanyList, Company\UpdatedCompany};
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Queries\CompanyQueries;
use App\Services\CompanyEntityService;
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
    }

    /**
     * Display a listing of the Companies.
     *
     * @param \App\Http\Requests\Company\PaginateCompanies $request
     * @param \App\Queries\CompanyQueries $queries
     * @return CompanyCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginateCompanies(PaginateCompanies $request, CompanyQueries $queries): CompanyCollection
    {
        $this->authorize('viewAny', Company::class);

        $pagination = $request->transformCompaniesQuery($queries->paginateCompaniesQuery($request))->apiPaginate();

        return CompanyCollection::make($pagination);
    }

    /**
     * Data for creating a new Company.
     *
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showCompanyFormData(): JsonResponse
    {
        $this->authorize('create', Company::class);

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
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeCompany(StoreCompanyRequest $request,
                                 CompanyEntityService $service): JsonResponse
    {
        $this->authorize('create', Company::class);

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showCompany(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json(
            UpdatedCompany::make($company)
        );
    }

    /**
     * Update the specified Company in storage.
     *
     * @param UpdateCompanyRequest $request
     * @param Company $company
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateCompany(UpdateCompanyRequest $request,
                                  CompanyEntityService $service,
                                  Company $company): JsonResponse
    {
        $this->authorize('update', $company);

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
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws \Throwable
     */
    public function updateCompanyContact(UpdateCompanyContact $request,
                                         CompanyEntityService $service,
                                         Company $company,
                                         Contact $contact): JsonResponse
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
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroyCompany(CompanyEntityService $service, Company $company): JsonResponse
    {
        $this->authorize('delete', $company);

        $service->deleteCompany($company);

        return response()->json(
            true,
            Response::HTTP_OK
        );
    }

    /**
     * Activate the specified Company from storage.
     *
     * @param Company $company
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function markAsActiveCompany(CompanyEntityService $service, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $service->markCompanyAsActive($company);

        return response()->json(
            true,
            Response::HTTP_OK
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
     * @param Company $company
     * @param CompanyEntityService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function markAsInactiveCompany(CompanyEntityService $service, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $service->markCompanyAsInactive($company);

        return response()->json(
            true,
            Response::HTTP_OK
        );
    }
}
