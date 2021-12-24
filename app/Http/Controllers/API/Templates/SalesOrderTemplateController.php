<?php

namespace App\Http\Controllers\API\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\{SalesOrderTemplate\StoreSalesOrderTemplate,
    SalesOrderTemplate\UpdateSalesOrderTemplate,
    SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplate};
use App\Http\Resources\{SalesOrderTemplate\SalesOrderTemplateList, SalesOrderTemplate\SalesOrderTemplateWithIncludes};
use App\Models\Template\SalesOrderTemplate;
use App\Queries\SalesOrderTemplateQueries;
use App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService;
use App\Services\Template\TemplateSchemaDataMapper;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};

class SalesOrderTemplateController extends Controller
{
    /**
     * Paginate the existing sales order template entities.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Queries\SalesOrderTemplateQueries $queries
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginateSalesOrderTemplates(Request $request, SalesOrderTemplateQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrderTemplate::class);

        $pagination = $queries->paginateSalesOrderTemplatesQuery($request)->apiPaginate();

        return SalesOrderTemplateList::collection($pagination);
    }

    /**
     * Show the existing sales order template entity.
     *
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showSalesOrderTemplate(SalesOrderTemplate $salesOrderTemplate): JsonResponse
    {
        $this->authorize('view', $salesOrderTemplate);

        return response()->json(
            SalesOrderTemplateWithIncludes::make($salesOrderTemplate),
            Response::HTTP_OK
        );
    }

    /**
     * Show template form for the existing sales order template.
     *
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @param \App\Services\Template\TemplateSchemaDataMapper $dataMapper
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showTemplateForm(SalesOrderTemplate $salesOrderTemplate, TemplateSchemaDataMapper $dataMapper): JsonResponse
    {
        $this->authorize('view', $salesOrderTemplate);

        return response()->json(
            $dataMapper->mapSalesOrderTemplateSchema($salesOrderTemplate)
        );
    }

    /**
     * Store a new sales order template entity.
     *
     * @param \App\Http\Requests\SalesOrderTemplate\StoreSalesOrderTemplate $request
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeSalesOrderTemplate(StoreSalesOrderTemplate $request,
                                            SalesOrderTemplateEntityService $entityService): JsonResponse
    {
        $this->authorize('create', SalesOrderTemplate::class);

        $resource = $entityService->createSalesOrderTemplate($request->getCreateSalesOrderTemplateData());

        return response()->json(
            SalesOrderTemplateWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Replicate the existing sales order template entity.
     *
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function replicateSalesOrderTemplate(SalesOrderTemplateEntityService $entityService, SalesOrderTemplate $salesOrderTemplate): JsonResponse
    {
        $this->authorize('create', SalesOrderTemplate::class);

        $resource = $entityService->replicateSalesOrderTemplate($salesOrderTemplate);

        return response()->json(
            SalesOrderTemplateWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the existing sales order template entity.
     *
     * @param \App\Http\Requests\SalesOrderTemplate\UpdateSalesOrderTemplate $request
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSalesOrderTemplate(UpdateSalesOrderTemplate $request,
                                             SalesOrderTemplateEntityService $entityService,
                                             SalesOrderTemplate $salesOrderTemplate): JsonResponse
    {
        $this->authorize('update', $salesOrderTemplate);

        $resource = $entityService->updateSalesOrderTemplate($request->getUpdateSalesOrderTemplateData(), $salesOrderTemplate);

        return response()->json(
            SalesOrderTemplateWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }


    /**
     * Update schema of the existing sales order template entity.
     *
     * @param \App\Http\Requests\SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplate $request
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSchemaOfSalesOrderTemplate(UpdateSchemaOfSalesOrderTemplate $request,
                                                     SalesOrderTemplateEntityService $entityService,
                                                     SalesOrderTemplate $salesOrderTemplate): JsonResponse
    {
        $this->authorize('update', $salesOrderTemplate);

        $resource = $entityService->updateSchemaOfSalesOrderTemplate($request->getUpdateSchemaOfSalesOrderTemplateData(), $salesOrderTemplate);

        return response()->json(
            SalesOrderTemplateWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Destroy the existing sales order template entity.
     *
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroySalesOrderTemplate(SalesOrderTemplateEntityService $entityService,
                                              SalesOrderTemplate $salesOrderTemplate): Response
    {
        $this->authorize('delete', $salesOrderTemplate);

        $entityService->deleteSalesOrderTemplate($salesOrderTemplate);

        return response()->noContent();
    }

    /**
     * Mark as active the existing sales order template entity.
     *
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsActiveSalesOrderTemplate(SalesOrderTemplateEntityService $entityService,
                                                   SalesOrderTemplate $salesOrderTemplate): Response
    {
        $this->authorize('update', $salesOrderTemplate);

        $entityService->markAsActiveSalesOrderTemplate($salesOrderTemplate);

        return response()->noContent();
    }

    /**
     * Mark as inactive the existing sales order template entity.
     *
     * @param \App\Services\SalesOrderTemplate\SalesOrderTemplateEntityService $entityService
     * @param \App\Models\Template\SalesOrderTemplate $salesOrderTemplate
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsInactiveSalesOrderTemplate(SalesOrderTemplateEntityService $entityService,
                                                     SalesOrderTemplate $salesOrderTemplate): Response
    {
        $this->authorize('update', $salesOrderTemplate);

        $entityService->markAsInactiveSalesOrderTemplate($salesOrderTemplate);

        return response()->noContent();
    }
}
