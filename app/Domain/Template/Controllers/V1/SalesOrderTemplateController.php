<?php

namespace App\Domain\Template\Controllers\V1;

use App\Domain\Template\Queries\SalesOrderTemplateQueries;
use App\Domain\Template\Requests\SalesOrderTemplate\StoreSalesOrderTemplateRequest;
use App\Domain\Template\Requests\SalesOrderTemplate\UpdateSalesOrderTemplateRequest;
use App\Domain\Template\Requests\SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplateRequest;
use App\Domain\Template\Resources\V1\SalesOrderTemplate\SalesOrderTemplateList;
use App\Domain\Template\Resources\V1\SalesOrderTemplate\SalesOrderTemplateWithIncludes;
use App\Domain\Template\Services\SalesOrderTemplateEntityService;
use App\Domain\Template\Services\TemplateSchemaDataMapper;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SalesOrderTemplateController extends Controller
{
    /**
     * Paginate the existing sales order template entities.
     *
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeSalesOrderTemplate(StoreSalesOrderTemplateRequest $request,
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSalesOrderTemplate(UpdateSalesOrderTemplateRequest $request,
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSchemaOfSalesOrderTemplate(UpdateSchemaOfSalesOrderTemplateRequest $request,
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
