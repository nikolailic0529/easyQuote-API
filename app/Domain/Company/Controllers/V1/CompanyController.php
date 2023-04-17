<?php

namespace App\Domain\Company\Controllers\V1;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Queries\AppointmentQueries;
use App\Domain\Appointment\Resources\V1\AppointmentListResource;
use App\Domain\Asset\Queries\AssetQueries;
use App\Domain\Asset\Resources\V1\AssetOfCompany;
use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Queries\AttachmentQueries;
use App\Domain\Attachment\Requests\CreateAttachmentRequest;
use App\Domain\Attachment\Resources\V1\AttachmentOfCompany;
use App\Domain\Attachment\Resources\V1\UnifiedAttachment;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Attachment\Services\UnifiedAttachmentDataMapper;
use App\Domain\Company\DataTransferObjects\BatchAttachCompanyAddressData;
use App\Domain\Company\DataTransferObjects\BatchAttachCompanyContactData;
use App\Domain\Company\Enum\CompanySource;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Queries\CompanyQueries;
use App\Domain\Company\Requests\DeleteCompanyRequest;
use App\Domain\Company\Requests\PaginateCompaniesRequest;
use App\Domain\Company\Requests\PartialUpdateCompanyRequest;
use App\Domain\Company\Requests\ShowCompanyFormDataRequest;
use App\Domain\Company\Requests\StoreCompanyRequest;
use App\Domain\Company\Requests\UpdateCompanyContactRequest;
use App\Domain\Company\Requests\UpdateCompanyRequest;
use App\Domain\Company\Resources\V1\CompanyCollection;
use App\Domain\Company\Resources\V1\CompanyWithIncludes;
use App\Domain\Company\Services\CompanyEntityService;
use App\Domain\Company\Services\CompanyQueryFilterDataProvider;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Queries\UnifiedNoteQueries;
use App\Domain\Note\Resources\V1\UnifiedNoteOfCompany;
use App\Domain\Note\Services\UnifiedNoteDataMapper;
use App\Domain\UnifiedQuote\Queries\UnifiedQuoteQueries;
use App\Domain\UnifiedQuote\Resources\V1\UnifiedQuoteOfCompany;
use App\Domain\UnifiedQuote\Services\UnifiedQuoteDataMapper;
use App\Domain\Worldwide\Queries\OpportunityQueries;
use App\Domain\Worldwide\Queries\SalesOrderQueries;
use App\Domain\Worldwide\Requests\Opportunity\PaginateOpportunitiesRequest;
use App\Domain\Worldwide\Resources\V1\Opportunity\OpportunityAsRelationResource;
use App\Domain\Worldwide\Resources\V1\SalesOrder\SalesOrderOfCompany;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class CompanyController extends Controller
{
    /**
     * Show opportunity filters.
     *
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
     * @throws AuthorizationException
     */
    public function paginateCompanies(PaginateCompaniesRequest $request, CompanyQueries $queries): CompanyCollection
    {
        $this->authorize('viewAny', Company::class);

        $pagination = $queries->baseCompaniesQuery($request)->apiPaginate();

        return \App\Domain\Company\Resources\V1\CompanyCollection::make($pagination);
    }

    /**
     * Data for creating a new Company.
     *
     * @throws AuthorizationException
     */
    public function showCompanyFormData(ShowCompanyFormDataRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        return \response()->json(
            data: $request->getFormData(),
        );
    }

    /**
     * Display a listing of the existing external companies.
     */
    public function getExternal(Request $request, CompanyQueries $queries): JsonResponse
    {
        return \response()->json(
            data: $queries->listOfExternalCompaniesBySource($request, CompanySource::EQ)->get(['id', 'name'])
        );
    }

    /**
     * Paginate existing external companies.
     */
    public function paginateExternalCompanies(Request $request, CompanyQueries $queries): AnonymousResourceCollection
    {
        $resource = $queries->paginateExternalCompaniesQuery($request)->apiPaginate();

        return \App\Domain\Company\Resources\V1\ExternalCompanyList::collection(
            $resource
        );
    }

    /**
     * Display a listing of all internal companies.
     */
    public function showListOfInternalCompanies(CompanyQueries $queries): JsonResponse
    {
        return \response()->json(
            data: $queries->listOfInternalCompaniesQuery()->get(['id', 'name'])
        );
    }

    /**
     * Display a listing of companies with related countries.
     */
    public function showInternalCompaniesWithCountries(Request $request, CompanyQueries $queries): JsonResponse
    {
        return \response()->json(
            data: $queries->listOfInternalCompaniesWithCountries($request)->get()
        );
    }

    /**
     * Store a newly created Company in storage.
     *
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

        return \response()->json(
            data: \App\Domain\Company\Resources\V1\CompanyWithIncludes::make($resource),
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified Company.
     *
     * @throws AuthorizationException
     */
    public function showCompany(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return \response()->json(
            data: CompanyWithIncludes::make($company)
        );
    }

    /**
     * Show a list of opportunity entities of the specified company entity.
     *
     * @throws AuthorizationException
     */
    public function showOpportunitiesOfCompany(
        PaginateOpportunitiesRequest $request,
        OpportunityQueries $opportunityQueries,
        Company $company
    ): AnonymousResourceCollection {
        $this->authorize('view', $company);

        $resource = $opportunityQueries
            ->listOpportunitiesOfCompanyQuery(company: $company, request: $request)
            ->get();

        return OpportunityAsRelationResource::collection($resource);
    }

    /**
     * Show a list of unified quote entities of the specified company entity.
     *
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

        return \response()->json(
            \App\Domain\Company\Resources\V1\CompanyWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Partially update the specified Company entity.
     *
     * @throws AuthorizationException
     */
    public function partiallyUpdateCompany(
        PartialUpdateCompanyRequest $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('update', $company);

        $resource = $service
            ->setCauser($request->user())
            ->partiallyUpdateCompany(company: $company, data: $request->getUpdateCompanyData());

        return \response()->json(
            CompanyWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Update a Contact of the Company.
     *
     * @throws \Throwable
     */
    public function updateCompanyContact(
        UpdateCompanyContactRequest $request,
        CompanyEntityService $service,
        Company $company,
        Contact $contact
    ): JsonResponse {
        $this->authorize('update', $company);

        $result = $service
            ->setCauser($request->user())
            ->updateCompanyContact($company, $contact, $request->getUpdateContactData());

        return \response()->json(
            data: $result,
            status: Response::HTTP_OK
        );
    }

    /**
     * Batch attach address to company.
     * @throws AuthorizationException
     */
    public function batchAttachAddressToCompany(
        Request $request,
        BatchAttachCompanyAddressData $data,
        CompanyEntityService $service,
        Company $company,
    ): Response {
        $this->authorize('update', $company);

        $service->setCauser($request->user())
            ->batchAttachAddressToCompany($company, $data);

        return \response()->noContent();
    }

    /**
     * Attach address to company.
     *
     * @throws AuthorizationException
     */
    public function attachAddressToCompany(
        CompanyEntityService $service,
        Company $company,
        Address $address,
    ): Response {
        $this->authorize('update', $company);

        $service->attachAddressToCompany($company, $address);

        return \response()->noContent();
    }

    /**
     * Detach address from company.
     *
     * @throws AuthorizationException
     */
    public function detachAddressFromCompany(
        CompanyEntityService $service,
        Company $company,
        Address $address
    ): Response {
        $this->authorize('update', $company);

        $service->detachAddressFromCompany($company, $address);

        return \response()->noContent();
    }

    /**
     * Batch attach address to company.
     * @throws AuthorizationException
     */
    public function batchAttachContactToCompany(
        Request $request,
        BatchAttachCompanyContactData $data,
        CompanyEntityService $service,
        Company $company,
    ): Response {
        $this->authorize('update', $company);

        $service->setCauser($request->user())
            ->batchAttachContactToCompany($company, $data);

        return \response()->noContent();
    }

    /**
     * Attach contact to company.
     *
     * @throws AuthorizationException
     */
    public function attachContactToCompany(
        CompanyEntityService $service,
        Company $company,
        Contact $contact,
    ): Response {
        $this->authorize('update', $company);

        $service->attachContactToCompany($company, $contact);

        return \response()->noContent();
    }

    /**
     * Detach contact from company.
     *
     * @throws AuthorizationException
     */
    public function detachContactFromCompany(
        CompanyEntityService $service,
        Company $company,
        Contact $contact
    ): Response {
        $this->authorize('update', $company);

        $service->detachContactFromCompany($company, $contact);

        return \response()->noContent();
    }

    /**
     * Show a list of existing attachments of the company entity.
     *
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
     * @throws AuthorizationException
     */
    public function storeAttachmentForCompany(
        CreateAttachmentRequest $request,
        AttachmentEntityService $entityService,
        Company $company
    ): JsonResponse {
        $this->authorize('view', $company);

        $request->validateFileHash($company);

        $resource = $entityService
            ->setCauser($request->user())
            ->createAttachmentForEntity(
                data: CreateAttachmentData::from($request),
                entity: $company,
            );

        return \response()->json(
            data: AttachmentOfCompany::make($resource),
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Delete the specified attachment of the company entity.
     *
     * @throws AuthorizationException
     */
    public function deleteAttachmentOfCompany(
        AttachmentEntityService $entityService,
        Company $company,
        Attachment $attachment
    ): JsonResponse {
        $this->authorize('view', $company);

        $entityService->deleteAttachment($attachment, $company);

        return \response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified Company from storage.
     *
     * @throws AuthorizationException
     */
    public function destroyCompany(
        DeleteCompanyRequest $request,
        CompanyEntityService $service,
        Company $company
    ): JsonResponse {
        $this->authorize('delete', $company);

        $service
            ->setCauser($request->user())
            ->deleteCompany($company);

        return \response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }

    /**
     * Activate the specified Company from storage.
     *
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

        return \response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
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

        return \response()->json(
            data: true,
            status: Response::HTTP_OK
        );
    }
}
