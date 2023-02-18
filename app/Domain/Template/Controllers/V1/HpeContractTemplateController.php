<?php

namespace App\Domain\Template\Controllers\V1;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\HpeContract\Queries\HpeContractTemplateQueries;
use App\Domain\Template\Contracts\HpeContractTemplate as Templates;
use App\Domain\Template\Requests\HpeContractTemplate\DeleteHpeContractTemplateRequest;
use App\Domain\Template\Requests\HpeContractTemplate\FilterHpeTemplatesRequest;
use App\Domain\Template\Requests\HpeContractTemplate\HpeTemplateDesignRequest;
use App\Domain\Template\Requests\HpeContractTemplate\StoreHpeContractTemplateRequest;
use App\Domain\Template\Requests\HpeContractTemplate\UpdateHpeContractTemplateRequest;
use App\Domain\Template\Resources\V1\TemplateCollection;
use App\Domain\Template\Resources\V1\TemplateResourceWithIncludes;
use App\Foundation\Http\Controller;
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
     */
    public function filterTemplates(FilterHpeTemplatesRequest $request): JsonResponse
    {
//        $this->authorize('viewAny', HpeContractTemplate::class);

        return response()->json(
            $request->getFilteredTemplates()
        );
    }

    /**
     * Display a listing of the resource by specified country.
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
     * @throws AuthorizationException
     */
    public function showTemplateSchema(HpeTemplateDesignRequest $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('view', $hpeContractTemplate);

        return response()->json(
            $request->getTemplateSchema()
        );
    }

    /**
     * Create a duplicate of the specified resource in repository.
     *
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
     * @throws AuthorizationException
     */
    public function storeTemplate(StoreHpeContractTemplateRequest $request): JsonResponse
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
     * @throws AuthorizationException
     */
    public function updateTemplate(UpdateHpeContractTemplateRequest $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
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
     * @throws AuthorizationException
     */
    public function destroyTemplate(DeleteHpeContractTemplateRequest $request, HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('delete', $hpeContractTemplate);

        return response()->json(
            $this->templates->delete($hpeContractTemplate)
        );
    }

    /**
     * Mark as activated the specified resource in storage.
     *
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
     * @throws AuthorizationException
     */
    public function deactivateTemplate(HpeContractTemplate $hpeContractTemplate): JsonResponse
    {
        $this->authorize('update', $hpeContractTemplate);

        return response()->json(
            $this->templates->deactivate($hpeContractTemplate)
        );
    }
}
