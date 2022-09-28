<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Enum\CompanySource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachment;
use App\Models\Opportunity;
use App\Services\Company\CompanyQueryFilterDataProvider;
use App\Services\Opportunity\OpportunityQueryFilterDataProvider;
use App\Http\Requests\Company\{DeleteCompany,
    PaginateCompanies,
    PartialUpdateCompany,
    ShowCompanyFormData,
    StoreCompanyRequest,
    UpdateCompanyContact,
    UpdateCompanyRequest};
use App\Http\Requests\Opportunity\PaginateOpportunities;
use Spatie\LaravelData\DataCollection;
use App\Http\Resources\{V1\Appointment\AppointmentListResource,
    V1\Asset\AssetOfCompany,
    V1\Attachment\AttachmentOfCompany,
    V1\Attachment\UnifiedAttachment,
    V1\Company\CompanyCollection,
    V1\Company\CompanyWithIncludes,
    V1\Note\UnifiedNoteOfCompany,
    V1\Opportunity\OpportunityList,
    V1\SalesOrder\SalesOrderOfCompany,
    V1\UnifiedQuote\UnifiedQuoteOfCompany};
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Queries\AppointmentQueries;
use App\Queries\AssetQueries;
use App\Queries\AttachmentQueries;
use App\Queries\CompanyQueries;
use App\Queries\OpportunityQueries;
use App\Queries\SalesOrderQueries;
use App\Queries\UnifiedNoteQueries;
use App\Queries\UnifiedQuoteQueries;
use App\Services\Attachment\AttachmentEntityService;
use App\Services\CompanyEntityService;
use App\Services\UnifiedAttachment\UnifiedAttachmentDataMapper;
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
    /**
     * Show opportunity filters.
     *
     * @param  Request  $request
     * @param  CompanyQueryFilterDataProvider  $dataProvider
     * @return DataCollection
     * @throws AuthorizationException
     */
    public function showCompanyFilters(Request $request, CompanyQueryFilterDataProvider $dataProvider): DataCollection
    {
        $this->authorize('viewAny', Company::class);

        return $dataProvider->getFilters($request);
    }

    /**
     * Display a listing of the Companies.
     *
     * @param  \App\Http\Requests\Company\PaginateCompanies  $request
     * @param  \App\Queries\CompanyQueries  $queries
     * @return CompanyCollection
     * @throws AuthorizationException
     */
    public function paginateCompanies(PaginateCompanies $request, CompanyQueries $queries): CompanyCollection
    {
        $this->authorize('viewAny', Company::class);

        $pagination = $queries->baseCompaniesQuery($request)->apiPaginate();

        return \App\Http\Resources\V1\Company\CompanyCollection::make($pagination);
    }

    /**
     * Data for creating a new Company.
     *
     * @param  ShowCompanyFormData  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showCompanyFormData(ShowCompanyFormData $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        return response()->json(
            data: $request->getFormData(),
        );
    }

    /**
     * Display a listing of the existing external companies.
     *
     * @param  CompanyQueries  $queries
     * @return JsonResponse
     */
    public function getExternal(Request $request, CompanyQueries $queries): JsonResponse
    {
        return response()->json(
            data: $queries->listOfExternalCompaniesBySource($request, CompanySource::EQ)->get(['id', 'name'])
        );
    }

    /**
     * Paginate existing external companies.
     *
     * @param  Request  $request
     * @param  CompanyQueries  $queries
     * @return AnonymousResourceCollection
     */
    public function paginateExternalCompanies(Request $request, CompanyQueries $queries): AnonymousResourceCollection
    {
        $resource = $queries->paginateExternalCompaniesQuery($request)->apiPaginate();

        return \App\Http\Resources\V1\Company\ExternalCompanyList::collection(
            $resource
        );
    }

    /**
     * Display a listing of all internal companies.
     *
     * @param  CompanyQueries  $queries
     * @return JsonResponse
     */
    public function showListOfInternalCompanies(CompanyQueries $queries): JsonResponse
    {
        return response()->json(
            data: $queries->listOfInternalCompaniesQuery()->get(['id', 'name'])
        );
    }

    /**
     * Display a listing of companies with related countries.
     *
     * @param  Request  $request
     * @param  CompanyQueries  $queries
     * @return JsonResponse
     */
    public function showInternalCompaniesWithCountries(Request $request, CompanyQueries $queries): JsonResponse
    {
        return response()->json(
            data: $queries->listOfInternalCompaniesWithCountries($request)->get()
        );
    }

    /**
     * Store a newly created Company in storage.
     *
     * @param  StoreCompanyRequest  $request
     * @param  CompanyEntityService  $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeCompany(
        StoreCompanyRequest $request,
        CompanyEntityService $service
    ): JsonResponse {
        $this->authorize('create', Company::class);

        $resource = $service
            ->setCauser($request->user())
            ->createCompany($request->getCreateCompanyData());

        return response()->json(
            data: \App\Http\Resources\V1\Company\CompanyWithIncludes::make($resource),
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified Company.
     *
     * @param  Company  $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showCompany(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json(
            data: CompanyWithIncludes::make($company)
        );
    }

    /**
     * Show a list of opportunity entities of the specified company entity.
     *
     * @param  PaginateOpportunities  $request
     * @param  OpportunityQueries  $opportunityQueries
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showOpportunitiesOfCompany(
        PaginateOpportunities $request,
        OpportunityQueries $opportunityQueries,
        Company $company
    ): AnonymousResourceCollection {

        $this->authorize('view', $company);

        $resource = $opportunityQueries
            ->listOpportunitiesOfCompanyQuery(company: $company, request: $request)
            ->get();

        return OpportunityList::collection($resource);
    }

    /**
     * Show a list of unified quote entities of the specified company entity.
     *
     * @param  Request  $request
     * @param  Company  $company
     * @param  UnifiedQuoteQueries  $unifiedQuoteQueries
     * @param  UnifiedQuoteDataMapper  $quoteDataMapper
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showQuotesOfCompany(
        Request $request,
        UnifiedQuoteQueries $unifiedQuoteQueries,
        UnifiedQuoteDataMapper $quoteDataMapper,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $entities = $unifiedQuoteQueries->listOfCompanyQuotesQuery(company: $company, request: $request)->get();

        $entities = $quoteDataMapper->mapUnifiedQuoteCollection($entities);

        return UnifiedQuoteOfCompany::collection($entities);
    }

    /**
     * Show a list of sales order entities of the specified company entity.
     *
     * @param  Request  $request
     * @param  SalesOrderQueries  $salesOrderQueries
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showSalesOrdersOfCompany(
        Request $request,
        SalesOrderQueries $salesOrderQueries,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $resource = $salesOrderQueries->listOfCompanySalesOrdersQuery(company: $company, request: $request)->get();

        return SalesOrderOfCompany::collection($resource);
    }

    /**
     * Show a list of unified note entities of the specified company entity.
     *
     * @param  Request  $request
     * @param  UnifiedNoteQueries  $unifiedNoteQueries
     * @param  UnifiedNoteDataMapper  $unifiedNoteDataMapper
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showUnifiedNotesOfCompany(
        Request $request,
        UnifiedNoteQueries $unifiedNoteQueries,
        UnifiedNoteDataMapper $unifiedNoteDataMapper,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $collection = $unifiedNoteQueries->listOfCompanyNotesQuery($company)->get();

        return UnifiedNoteOfCompany::collection($collection);
    }

    /**
     * Show a list of asset entities of the specified company entity.
     *
     * @param  Request  $request
     * @param  AssetQueries  $assetQueries
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAssetsOfCompany(
        Request $request,
        AssetQueries $assetQueries,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $resource = $assetQueries->listOfCompanyAssetsQuery($company)->get();

        return AssetOfCompany::collection($resource);
    }

    /**
     * List appointments linked to company.
     *
     * @param  Request  $request
     * @param  AppointmentQueries  $appointmentQueries
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfCompany(
        Request $request,
        AppointmentQueries $appointmentQueries,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($company, $request)->get();

        return AppointmentListResource::collection($resource);
    }

    /**
     * Update the specified Company in storage.
     *
     * @param  UpdateCompanyRequest  $request
     * @param  Company  $company
     * @param  CompanyEntityService  $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateCompany(
        UpdateCompanyRequest $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('update', $company);

        $resource = $service
            ->setCauser($request->user())
            ->updateCompany(company: $company, data: $request->getUpdateCompanyData());

        return response()->json(
            \App\Http\Resources\V1\Company\CompanyWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Partially update the specified Company entity.
     *
     * @param  PartialUpdateCompany  $request
     * @param  CompanyEntityService  $service
     * @param  Company  $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function partiallyUpdateCompany(
        PartialUpdateCompany $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('update', $company);

        $resource = $service
            ->setCauser($request->user())
            ->partiallyUpdateCompany(company: $company, data: $request->getUpdateCompanyData());

        return response()->json(
            CompanyWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Update a Contact of the Company.
     *
     * @param  UpdateCompanyContact  $request
     * @param  Company  $company
     * @param  Contact  $contact
     * @param  CompanyEntityService  $service
     * @return JsonResponse
     * @throws \Throwable
     */
    public function updateCompanyContact(
        UpdateCompanyContact $request,
        CompanyEntityService $service,
        Company $company,
        Contact $contact
    ): JsonResponse {
        $this->authorize('update', $company);

        $result = $service
            ->setCauser($request->user())
            ->updateCompanyContact($company, $contact, $request->getUpdateContactData());

        return response()->json(
            data: $result,
            status: Response::HTTP_OK
        );
    }


    /**
     * Detach address from company.
     *
     * @param  CompanyEntityService  $service
     * @param  Company  $company
     * @param  Address  $address
     * @return Response
     * @throws AuthorizationException
     */
    public function detachAddressFromCompany(
        CompanyEntityService $service,
        Company $company,
        Address $address
    ): Response {
        $this->authorize('update', $company);

        $service->detachAddressFromCompany($company, $address);

        return response()->noContent();
    }


    /**
     * Detach contact from company.
     *
     * @param  CompanyEntityService  $service
     * @param  Company  $company
     * @param  Contact  $contact
     * @return Response
     * @throws AuthorizationException
     */
    public function detachContactFromCompany(
        CompanyEntityService $service,
        Company $company,
        Contact $contact
    ): Response {
        $this->authorize('update', $company);

        $service->detachContactFromCompany($company, $contact);

        return response()->noContent();
    }

    /**
     * Show a list of existing attachments of the company entity.
     *
     * @param  AttachmentQueries  $attachmentQueries
     * @param  UnifiedAttachmentDataMapper  $dataMapper
     * @param  Company  $company
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAttachmentsOfCompany(
        AttachmentQueries $attachmentQueries,
        UnifiedAttachmentDataMapper $dataMapper,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $collection = $attachmentQueries->listOfCompanyUnifiedAttachmentsQuery($company)->get();

        $collection = $dataMapper->mapUnifiedAttachmentCollection($collection);

        return UnifiedAttachment::collection($collection);
    }

    /**
     * Store a new attachment for the company entity.
     *
     * @param  CreateAttachment  $request
     * @param  AttachmentEntityService  $entityService
     * @param  Company  $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeAttachmentForCompany(
        CreateAttachment $request,
        AttachmentEntityService $entityService,
        Company $company
    ): JsonResponse {
        $this->authorize('view', $company);

        $resource = $entityService->createAttachmentForEntity(
            file: $request->getUploadedFile(),
            type: $request->getAttachmentType(),
            entity: $company
        );

        return response()->json(
            data: AttachmentOfCompany::make($resource),
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Delete the specified attachment of the company entity.
     *
     * @param  AttachmentEntityService  $entityService
     * @param  Company  $company
     * @param  Attachment  $attachment
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deleteAttachmentOfCompany(
        AttachmentEntityService $entityService,
        Company $company,
        Attachment $attachment
    ): JsonResponse {
        $this->authorize('view', $company);

        $entityService->deleteAttachment($attachment, $company);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified Company from storage.
     *
     * @param  DeleteCompany  $request
     * @param  CompanyEntityService  $service
     * @param  Company  $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyCompany(
        DeleteCompany $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('delete', $company);

        $service
            ->setCauser($request->user())
            ->deleteCompany($company);

        return response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }

    /**
     * Activate the specified Company from storage.
     *
     * @param  Company  $company
     * @param  CompanyEntityService  $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function markAsActiveCompany(
        Request $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('update', $company);

        $service
            ->setCauser($request->user())
            ->markCompanyAsActive($company);

        return response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
     * @param  Request  $request
     * @param  CompanyEntityService  $service
     * @param  Company  $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function markAsInactiveCompany(
        Request $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('update', $company);

        $service
            ->setCauser($request->user())
            ->markCompanyAsInactive($company);

        return response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }
}
