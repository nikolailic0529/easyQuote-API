<?php

namespace App\Domain\Template\Controllers\V1;

use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Template\Contracts\ContractTemplateRepositoryInterface as Repository;
use App\Domain\Template\Queries\ContractTemplateQueries;
use App\Domain\Template\Requests\ContractTemplate\DeleteContractTemplateRequest;
use App\Domain\Template\Requests\ContractTemplate\FilterContractTemplatesByCompanyVendorCountryRequest;
use App\Domain\Template\Requests\ContractTemplate\StoreContractTemplateRequest;
use App\Domain\Template\Requests\ContractTemplate\UpdateContractTemplateRequest;
use App\Domain\Template\Resources\V1\TemplateCollection;
use App\Domain\Template\Resources\V1\TemplateResourceListing;
use App\Domain\Template\Resources\V1\{TemplateResourceWithIncludes};
use App\Domain\Template\Services\ContractTemplateService;
use App\Domain\Template\Services\TemplateSchemaDataMapper;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Foundation\Http\Controller;
use App\Foundation\Validation\Exceptions\ValidationException;
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
     */
    public function index(Request $request, ContractTemplateQueries $queries): JsonResponse
    {
        $resource = $queries->paginateContractTemplatesQuery($request)->apiPaginate();

        return response()->json(TemplateCollection::make($resource));
    }

    /**
     * Display a listing of the Quote Templates by specified Country.
     *
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
     * @throws ValidationException
     */
    public function store(StoreContractTemplateRequest $request, ContractTemplateService $service): JsonResponse
    {
        $template = $service->createContractTemplate($request->getCreateContractTemplateData());

        return response()->json(
            TemplateResourceWithIncludes::make($template),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified Contract Template.
     */
    public function show(ContractTemplate $contract_template): JsonResponse
    {
        return response()->json(TemplateResourceWithIncludes::make($contract_template));
    }

    /**
     * Filter Worldwide Contract Service Contract Templates by specified company.
     *
     * @throws AuthorizationException
     */
    public function filterWorldwideContractContractTemplates(FilterContractTemplatesByCompanyVendorCountryRequest $request,
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
     * @throws AuthorizationException
     */
    public function filterWorldwidePackContractTemplates(FilterContractTemplatesByCompanyVendorCountryRequest $request,
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
     * @throws ValidationException
     */
    public function update(UpdateContractTemplateRequest $request,
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
     */
    public function destroy(DeleteContractTemplateRequest $request, ContractTemplate $contractTemplate): JsonResponse
    {
        return response()->json(
            $this->contractTemplate->delete($contractTemplate->getKey())
        );
    }

    /**
     * Activate the specified Contract Template from storage.
     *
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
