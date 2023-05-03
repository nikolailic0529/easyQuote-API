<?php

namespace App\Domain\Template\Controllers\V1;

use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Queries\QuoteTemplateQueries;
use App\Domain\Template\Requests\QuoteTemplate\DeleteTemplateRequest;
use App\Domain\Template\Requests\QuoteTemplate\FilterQuoteTemplatesByCompanyRequest;
use App\Domain\Template\Requests\QuoteTemplate\FilterQuoteTemplatesByMultipleVendorsRequest;
use App\Domain\Template\Requests\QuoteTemplate\StoreQuoteTemplateRequest;
use App\Domain\Template\Requests\QuoteTemplate\UpdateQuoteTemplateRequest;
use App\Domain\Template\Resources\V1\QuoteTemplate\PaginatedQuoteTemplate;
use App\Domain\Template\Resources\V1\QuoteTemplate\QuoteTemplateWithIncludes;
use App\Domain\Template\Resources\V1\{TemplateResourceListing};
use App\Domain\Template\Services\QuoteTemplateService;
use App\Domain\Template\Services\TemplateSchemaDataMapper;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Http\Controller;
use App\Foundation\Validation\Exceptions\ValidationException;
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
     */
    public function index(Request $request, QuoteTemplateQueries $queries): AnonymousResourceCollection
    {
        $resource = $queries->paginateQuoteTemplatesQuery($request)->apiPaginate();

        return PaginatedQuoteTemplate::collection($resource);
    }

    /**
     * Display a listing of the Quote Templates belong to specified country.
     *
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
     * @throws AuthorizationException
     */
    public function filterRescueTemplates(FilterQuoteTemplatesByMultipleVendorsRequest $request, QuoteTemplateQueries $queries): JsonResponse
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
     * @throws AuthorizationException
     */
    public function filterWorldwideTemplates(FilterQuoteTemplatesByMultipleVendorsRequest $request, QuoteTemplateQueries $queries): JsonResponse
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
     * @throws AuthorizationException
     */
    public function filterWorldwidePackTemplates(FilterQuoteTemplatesByCompanyRequest $request, QuoteTemplateQueries $queries): JsonResponse
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
     * @throws AuthorizationException
     */
    public function filterWorldwideContractTemplates(FilterQuoteTemplatesByMultipleVendorsRequest $request, QuoteTemplateQueries $queries): JsonResponse
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
     * @throws \App\Foundation\Validation\Exceptions\ValidationException
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
     */
    public function show(QuoteTemplate $template): JsonResponse
    {
        return response()->json(QuoteTemplateWithIncludes::make($template));
    }

    /**
     * Update the specified Quote Template in storage.
     *
     * @throws ValidationException
     */
    public function update(UpdateQuoteTemplateRequest $request, QuoteTemplate $template, QuoteTemplateService $service): JsonResponse
    {
        $resource = $service->updateQuoteTemplate($template, $request->getUpdateQuoteTemplateData());

        return response()->json(QuoteTemplateWithIncludes::make($resource));
    }

    /**
     * Remove the specified Quote Template from storage.
     */
    public function destroy(DeleteTemplateRequest $request, QuoteTemplate $template, QuoteTemplateService $service): Response
    {
        $service->deleteQuoteTemplate($template);

        return response()->noContent();
    }

    /**
     * Activate the specified Quote Template from storage.
     *
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
