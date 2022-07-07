<?php

namespace App\Http\Controllers\API\V1\Templates;

use App\Contracts\Repositories\QuoteTemplate\HpeContractTemplate as Templates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HpeContractTemplate\{DeleteHpeContractTemplate,
    FilterHpeTemplates,
    HpeTemplateDesign,
    StoreHpeContractTemplate,
    UpdateHpeContractTemplate,};
use App\Http\Resources\V1\TemplateRepository\TemplateCollection;
use App\Http\Resources\V1\TemplateRepository\TemplateResourceWithIncludes;
use App\Models\Template\HpeContractTemplate;
use App\Queries\HpeContractTemplateQueries;
use App\Services\ProfileHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HpeContractTemplateController extends Controller
{
    protected Templates $templates;

    public function __construct(Templates $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param HpeContractTemplateQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateTemplates(Request $request, HpeContractTemplateQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', HpeContractTemplate::class);

        return response()->json(
            TemplateCollection::make(
                $queries->paginateHpeContractTemplatesQuery($request)->apiPaginate()
            )
        );
    }

    /**
     * Filter Hpe Contract Templates by specified clause.
     *
     * @param FilterHpeTemplates $request
     * @return JsonResponse
     */
    public function filterTemplates(FilterHpeTemplates $request): JsonResponse
    {
//        $this->authorize('viewAny', HpeContractTemplate::class);

        return response()->json(
            $request->getFilteredTemplates()
        );
    }

    /**
     * Display a listing of the resource by specified country.
     *
     * @param string $country
     * @return JsonResponse
     */
    public function filterTemplatesByCountry(string $country): JsonResponse
    {
//        $this->authorize('viewAny', HpeContractTemplate::class);

        return response()->json(
            $this->templates->findByCountry($country)
        );
    }

    /**
     * Retrieve data for template designer.
     *
     * @param HpeTemplateDesign $request
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showTemplateSchema(HpeTemplateDesign $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('view', $hpeContractTemplate);

        return response()->json(
            $request->getTemplateSchema()
        );
    }

    /**
     * Create a duplicate of the specified resource in repository.
     *
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function replicateTemplate(HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('view', $hpeContractTemplate);
        $this->authorize('create', HpeContractTemplate::class);

        return response()->json(
            $this->templates->copy($hpeContractTemplate)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreHpeContractTemplate $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeTemplate(StoreHpeContractTemplate $request): JsonResponse
    {
        $this->authorize('create', HpeContractTemplate::class);

        $resource = $this->templates->create($request->validated());

        return response()->json(
            TemplateResourceWithIncludes::make($resource),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     *
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showTemplate(HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('view', $hpeContractTemplate);

        return response()->json(
            TemplateResourceWithIncludes::make($hpeContractTemplate)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateHpeContractTemplate $request
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateTemplate(UpdateHpeContractTemplate $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('update', $hpeContractTemplate);

        return response()->json(
            TemplateResourceWithIncludes::make(
                $this->templates->update($hpeContractTemplate, $request->validated())
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DeleteHpeContractTemplate $request
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyTemplate(DeleteHpeContractTemplate $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('delete', $hpeContractTemplate);

        return response()->json(
            tap(
                $this->templates->delete($hpeContractTemplate),
                fn() => ProfileHelper::flushHpeContractTemplateProfiles($hpeContractTemplate)
            )
        );
    }

    /**
     * Mark as activated the specified resource in storage.
     *
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activateTemplate(HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('update', $hpeContractTemplate);

        return response()->json(
            $this->templates->activate($hpeContractTemplate)
        );
    }

    /**
     * Mark as deactivated the specified resource in storage.
     *
     * @param HpeContractTemplate $hpeContractTemplate
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivateTemplate(HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('update', $hpeContractTemplate);

        return response()->json(
            tap(
                $this->templates->deactivate($hpeContractTemplate),
                fn() => ProfileHelper::flushHpeContractTemplateProfiles($hpeContractTemplate, 'deactivated')
            )
        );
    }
}
