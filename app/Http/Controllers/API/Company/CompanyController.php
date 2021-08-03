<?php

namespace App\Http\Controllers\API\Company;

use App\Contracts\Repositories\{CompanyRepositoryInterface as CompanyRepository,
    VendorRepositoryInterface as VendorRepository};
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\{PaginateCompanies, StoreCompanyRequest, UpdateCompanyContact, UpdateCompanyRequest};
use App\Http\Requests\Opportunity\PaginateOpportunities;
use App\Http\Resources\{Asset\AssetOfCompany,
    Company\CompanyCollection,
    Company\ExternalCompanyList,
    Company\UpdatedCompany,
    Note\UnifiedNoteOfCompany,
    Opportunity\OpportunityList,
    SalesOrder\SalesOrderOfCompany,
    UnifiedQuote\UnifiedQuote};
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Queries\AssetQueries;
use App\Queries\CompanyQueries;
use App\Queries\OpportunityQueries;
use App\Queries\SalesOrderQueries;
use App\Queries\UnifiedNoteQueries;
use App\Queries\UnifiedQuoteQueries;
use App\Services\CompanyEntityService;
use App\Services\UnifiedNote\UnifiedNoteDataMapper;
use App\Services\UnifiedQuote\UnifiedQuoteDataMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use function response;

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
     * Show a list of opportunity entities of the specified company entity.
     *
     * @param PaginateOpportunities $request
     * @param OpportunityQueries $opportunityQueries
     * @param Company $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showOpportunitiesOfCompany(PaginateOpportunities $request,
                                               OpportunityQueries $opportunityQueries,
                                               Company $company): AnonymousResourceCollection
    {

        $this->authorize('view', $company);

        $query = $opportunityQueries->listOkOpportunitiesOfCompanyQuery(company: $company, request: $request);

        $resource = $request->transformOpportunitiesQuery($query)->get();

        return OpportunityList::collection($resource);
    }

    /**
     * Show a list of unified quote entities of the specified company entity.
     *
     * @param Request $request
     * @param Company $company
     * @param UnifiedQuoteQueries $unifiedQuoteQueries
     * @param UnifiedQuoteDataMapper $quoteDataMapper
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showQuotesOfCompany(Request $request,
                                        UnifiedQuoteQueries $unifiedQuoteQueries,
                                        UnifiedQuoteDataMapper $quoteDataMapper,
                                        Company $company): AnonymousResourceCollection
    {
        $this->authorize('view', $company);

        $entities = $unifiedQuoteQueries->listOfCompanyQuotesQuery(company: $company, request: $request)->get();

        $entities = $quoteDataMapper->mapUnifiedQuoteCollection($entities);

        return UnifiedQuote::collection($entities);
    }

    /**
     * Show a list of sales order entities of the specified company entity.
     *
     * @param Request $request
     * @param SalesOrderQueries $salesOrderQueries
     * @param Company $company
     * @throws AuthorizationException
     */
    public function showSalesOrdersOfCompany(Request $request,
                                             SalesOrderQueries $salesOrderQueries,
                                             Company $company): AnonymousResourceCollection
    {
        $this->authorize('view', $company);

        $resource = $salesOrderQueries->listOfCompanySalesOrdersQuery(company: $company, request: $request)->get();

        return SalesOrderOfCompany::collection($resource);
    }

    /**
     * Show a list of unified note entities of the specified company entity.
     *
     * @param Request $request
     * @param UnifiedNoteQueries $unifiedNoteQueries
     * @param UnifiedNoteDataMapper $unifiedNoteDataMapper
     * @param Company $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showUnifiedNotesOfCompany(Request $request,
                                              UnifiedNoteQueries $unifiedNoteQueries,
                                              UnifiedNoteDataMapper $unifiedNoteDataMapper,
                                              Company $company): AnonymousResourceCollection
    {
        $this->authorize('view', $company);

        $collection = $unifiedNoteQueries->listOfCompanyNotesQuery($company)->get();

        $resource = $unifiedNoteDataMapper->mapUnifiedNoteCollection($collection);

        return UnifiedNoteOfCompany::collection($resource);
    }

    /**
     * Show a list of asset entities of the specified company entity.
     *
     * @param Request $request
     * @param AssetQueries $assetQueries
     * @param Company $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAssetsOfCompany(Request $request,
                                        AssetQueries $assetQueries,
                                        Company $company): AnonymousResourceCollection
    {
        $this->authorize('view', $company);

        $resource = $assetQueries->listOfCompanyAssetsQuery($company)->get();

        return AssetOfCompany::collection($resource);
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
