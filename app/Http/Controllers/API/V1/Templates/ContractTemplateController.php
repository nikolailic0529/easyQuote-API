<?php

namespace App\Http\Controllers\API\V1\Templates;

use App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface as Repository;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractTemplate\DeleteContractTemplate;
use App\Http\Requests\ContractTemplate\FilterContractTemplatesByCompanyVendorCountry;
use App\Http\Requests\ContractTemplate\StoreContractTemplate;
use App\Http\Requests\ContractTemplate\UpdateContractTemplate;
use App\Http\Resources\V1\TemplateRepository\{TemplateResourceWithIncludes};
use App\Http\Resources\V1\TemplateRepository\TemplateCollection;
use App\Http\Resources\V1\TemplateRepository\TemplateResourceListing;
use App\Models\SalesOrder;
use App\Models\Template\ContractTemplate;
use App\Queries\ContractTemplateQueries;
use App\Services\ContractTemplateService;
use App\Services\Exceptions\ValidationException;
use App\Services\Template\TemplateSchemaDataMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContractTemplateController extends Controller
{
    protected Repository $contractTemplate;

    public function __construct(Repository $contractTemplate)
    {
        $this->contractTemplate = $contractTemplate;
        $this->authorizeResource(ContractTemplate::class, 'contract_template');
    }

    /**
     * Display a listing of the Contract Templates.
     *
     * @param Request $request
     * @param ContractTemplateQueries $queries
     * @return JsonResponse
     */
    public function index(Request $request, ContractTemplateQueries $queries): JsonResponse
    {
        $resource = $queries->paginateContractTemplatesQuery($request)->apiPaginate();

        return response()->json(TemplateCollection::make($resource));
    }

    /**
     * Display a listing of the Quote Templates by specified Country.
     *
     * @param string $country
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function country(string $country): JsonResponse
    {
        $this->authorize('viewAny', ContractTemplate::class);

        $resource = $this->contractTemplate->country($country);

        return response()->json(TemplateResourceListing::collection($resource));
    }

    /**
     * Store a newly created Quote Template in storage.
     *
     * @param StoreContractTemplate $request
     * @param ContractTemplateService $service
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(StoreContractTemplate $request, ContractTemplateService $service): JsonResponse
    {
        $template = $service->createContractTemplate($request->getCreateContractTemplateData());

        return response()->json(
            TemplateResourceWithIncludes::make($template),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified Contract Template.
     *
     * @param ContractTemplate $contract_template
     * @return JsonResponse
     */
    public function show(ContractTemplate $contract_template): JsonResponse
    {
        return response()->json(TemplateResourceWithIncludes::make($contract_template));
    }

    /**
     * Filter Worldwide Contract Service Contract Templates by specified company.
     *
     * @param FilterContractTemplatesByCompanyVendorCountry $request
     * @param ContractTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterWorldwideContractContractTemplates(FilterContractTemplatesByCompanyVendorCountry $request,
                                                             ContractTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);

        $data = $queries->filterWorldwideContractServiceContractTemplatesQuery(
            $request->getCompanyId(),
            $request->getVendorId(),
            $request->getCountryId()
        )->get();

        return response()->json(
            $data
        );
    }

    /**
     * Filter Worldwide Pack Contract Templates by specified company.
     *
     * @param FilterContractTemplatesByCompanyVendorCountry $request
     * @param ContractTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterWorldwidePackContractTemplates(FilterContractTemplatesByCompanyVendorCountry $request,
                                                         ContractTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);

        $data = $queries->filterWorldwidePackContractTemplatesQuery(
            $request->getCompanyId(),
            $request->getVendorId(),
            $request->getCountryId()
        )->get();

        return response()->json(
            $data
        );
    }

    /**
     * Update the specified Contract Template in storage.
     *
     * @param UpdateContractTemplate $request
     * @param ContractTemplate $contractTemplate
     * @param ContractTemplateService $service
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(UpdateContractTemplate $request,
                           ContractTemplate $contractTemplate,
                           ContractTemplateService $service): JsonResponse
    {
        $template = $service->updateContractTemplate(
            $contractTemplate,
            $request->getUpdateContractTemplateData()
        );

        return response()->json(TemplateResourceWithIncludes::make($template));
    }

    /**
     * Remove the specified Contract Template from storage.
     *
     * @param DeleteContractTemplate $request
     * @param ContractTemplate $contractTemplate
     * @return JsonResponse
     */
    public function destroy(DeleteContractTemplate $request, ContractTemplate $contractTemplate): JsonResponse
    {
        return response()->json(
            $this->contractTemplate->delete($contractTemplate->getKey())
        );
    }

    /**
     * Activate the specified Contract Template from storage.
     *
     * @param ContractTemplate $contract_template
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(ContractTemplate $contract_template): JsonResponse
    {
        $this->authorize('update', $contract_template);

        return response()->json(
            $this->contractTemplate->activate($contract_template->getKey())
        );
    }

    /**
     * Deactivate the specified Contract Template from storage.
     *
     * @param ContractTemplate $contract_template
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(ContractTemplate $contract_template): JsonResponse
    {
        $this->authorize('update', $contract_template);

        return response()->json(
            $this->contractTemplate->deactivate($contract_template->getKey())
        );
    }

    /**
     * Get Data for Template Designer.
     *
     * @param ContractTemplate $contractTemplate
     * @param TemplateSchemaDataMapper $schemaDataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showTemplateForm(ContractTemplate $contractTemplate, TemplateSchemaDataMapper $schemaDataMapper): JsonResponse
    {
        $this->authorize('view', $contractTemplate);

        return response()->json(
            $schemaDataMapper->mapContractTemplateSchema($contractTemplate)
        );
    }

    /**
     * Create copy of the specified Contract Template.
     *
     * @param ContractTemplate $contract_template
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function copy(ContractTemplate $contract_template): JsonResponse
    {
        $this->authorize('copy', $contract_template);

        return response()->json(
            $this->contractTemplate->copy($contract_template->getKey())
        );
    }
}
