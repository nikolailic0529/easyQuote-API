<?php

namespace App\Http\Controllers\API\V1\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteTemplate\{DeleteTemplate,
    FilterQuoteTemplatesByCompany,
    FilterQuoteTemplatesByMultipleVendors,
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest};
use App\Http\Resources\V1\QuoteTemplate\PaginatedQuoteTemplate;
use App\Http\Resources\V1\QuoteTemplate\QuoteTemplateWithIncludes;
use App\Http\Resources\V1\TemplateRepository\{TemplateResourceListing};
use App\Models\Quote\WorldwideQuote;
use App\Models\Template\QuoteTemplate;
use App\Queries\QuoteTemplateQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\QuoteTemplateService;
use App\Services\Template\TemplateSchemaDataMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class QuoteTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(QuoteTemplate::class, 'template');
    }

    /**
     * Display a listing of the Quote Templates.
     *
     * @param Request $request
     * @param QuoteTemplateQueries $queries
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, QuoteTemplateQueries $queries): AnonymousResourceCollection
    {
        $resource = $queries->paginateQuoteTemplatesQuery($request)->apiPaginate();

        return PaginatedQuoteTemplate::collection($resource);
    }

    /**
     * Display a listing of the Quote Templates belong to specified country.
     *
     * @param string $country
     * @param QuoteTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterTemplatesByCountry(string $country, QuoteTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', QuoteTemplate::class);

        return response()->json(TemplateResourceListing::collection(
            $queries->quoteTemplatesBelongToCountryQuery($country)->get()
        ));
    }

    /**
     * Filter Rescue Quote Templates by multiple vendors.
     *
     * @param FilterQuoteTemplatesByMultipleVendors $request
     * @param QuoteTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterRescueTemplates(FilterQuoteTemplatesByMultipleVendors $request, QuoteTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', QuoteTemplate::class);

        return response()->json(
            $queries->filterRescueQuoteTemplatesByMultipleVendorsQuery(
                $request->input('company_id'),
                $request->input('vendors'),
                $request->input('country_id')
            )->get()
        );
    }

    /**
     * Filter Worldwide Quote Templates by multiple vendors.
     *
     * @param FilterQuoteTemplatesByMultipleVendors $request
     * @param QuoteTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterWorldwideTemplates(FilterQuoteTemplatesByMultipleVendors $request, QuoteTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        return response()->json(
            $queries->filterWorldwideQuoteTemplatesByMultipleVendorsQuery(
                $request->input('company_id'),
                $request->input('vendors'),
                $request->input('country_id')
            )->get()
        );
    }

    /**
     * Filter Worldwide Pack Quote Templates by multiple vendors.
     *
     * @param FilterQuoteTemplatesByCompany $request
     * @param QuoteTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterWorldwidePackTemplates(FilterQuoteTemplatesByCompany $request, QuoteTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        return response()->json(
            $queries->filterWorldwidePackQuoteTemplatesByCompanyQuery(
                $request->input('company_id')
            )->get()
        );
    }

    /**
     * Filter Worldwide Contract Quote Templates by multiple vendors.
     *
     * @param FilterQuoteTemplatesByMultipleVendors $request
     * @param QuoteTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function filterWorldwideContractTemplates(FilterQuoteTemplatesByMultipleVendors $request, QuoteTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        return response()->json(
            $queries->filterWorldwideContractQuoteTemplatesByMultipleVendorsQuery(
                $request->input('company_id'),
                $request->input('vendors'),
                $request->input('country_id')
            )->get()
        );
    }

    /**
     * Store a newly created Quote Template in storage.
     *
     * @param StoreQuoteTemplateRequest $request
     * @param QuoteTemplateService $service
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(StoreQuoteTemplateRequest $request, QuoteTemplateService $service): JsonResponse
    {
        $resource = $service->createQuoteTemplate($request->getCreateQuoteTemplateData());

        return response()->json(
            QuoteTemplateWithIncludes::make($resource)
        );
    }

    /**
     * Display the specified Quote Template.
     *
     * @param QuoteTemplate $template
     * @return JsonResponse
     */
    public function show(QuoteTemplate $template): JsonResponse
    {
        return response()->json(QuoteTemplateWithIncludes::make($template));
    }

    /**
     * Update the specified Quote Template in storage.
     *
     * @param UpdateQuoteTemplateRequest $request
     * @param QuoteTemplate $template
     * @param QuoteTemplateService $service
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(UpdateQuoteTemplateRequest $request, QuoteTemplate $template, QuoteTemplateService $service): JsonResponse
    {
        $resource = $service->updateQuoteTemplate($template, $request->getUpdateQuoteTemplateData());

        return response()->json(QuoteTemplateWithIncludes::make($resource));
    }

    /**
     * Remove the specified Quote Template from storage.
     *
     * @param DeleteTemplate $request
     * @param QuoteTemplate $template
     * @param QuoteTemplateService $service
     * @return Response
     */
    public function destroy(DeleteTemplate $request, QuoteTemplate $template, QuoteTemplateService $service): Response
    {
        $service->deleteQuoteTemplate($template);

        return response()->noContent();
    }

    /**
     * Activate the specified Quote Template from storage.
     *
     * @param QuoteTemplate $template
     * @param QuoteTemplateService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function activate(QuoteTemplate $template, QuoteTemplateService $service): Response
    {
        $this->authorize('update', $template);

        $service->activateQuoteTemplate($template);

        return response()->noContent();
    }

    /**
     * Deactivate the specified Quote Template from storage.
     *
     * @param QuoteTemplate $template
     * @param QuoteTemplateService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function deactivate(QuoteTemplate $template, QuoteTemplateService $service): Response
    {
        $this->authorize('update', $template);

        $service->deactivateQuoteTemplate($template);

        return response()->noContent();
    }

    /**
     * Show template schema of the specified Quote Template.
     *
     * @param QuoteTemplate $template
     * @param TemplateSchemaDataMapper $schemaDataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showTemplateForm(QuoteTemplate $template, TemplateSchemaDataMapper $schemaDataMapper): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json(
            $schemaDataMapper->mapQuoteTemplateSchema($template)
        );
    }

    /**
     * Create copy of the specified Quote Template.
     *
     * @param QuoteTemplate $template
     * @param QuoteTemplateService $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function copy(QuoteTemplate $template, QuoteTemplateService $service): JsonResponse
    {
        $this->authorize('copy', $template);

        return response()->json(
            $service->replicateQuoteTemplate($template)
        );
    }
}
