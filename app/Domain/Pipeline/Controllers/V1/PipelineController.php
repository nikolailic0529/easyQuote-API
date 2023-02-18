<?php

namespace App\Domain\Pipeline\Controllers\V1;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Queries\PipelineQueries;
use App\Domain\Pipeline\Requests\BulkCreateOrUpdatePipelinesRequest;
use App\Domain\Pipeline\Requests\ShowDefaultPipelineRequest;
use App\Domain\Pipeline\Requests\StorePipelineRequest;
use App\Domain\Pipeline\Requests\UpdatePipelineRequest;
use App\Domain\Pipeline\Resources\V1\OpportunityFormSchemaOfPipeline;
use App\Domain\Pipeline\Resources\V1\PipelineWithIncludes;
use App\Domain\Worldwide\Models\Opportunity;
use App\Foundation\Http\Controller;
use App\Foundation\Http\Services\RequestQueryFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PipelineController extends Controller
{
    /**
     * Show a list of existing pipeline entities.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showListOfPipelines(Request $request,
                                        PipelineQueries $queries,
                                        RequestQueryFilter $requestQueryFilter): JsonResponse
    {
        $this->authorize('viewAny', Pipeline::class);

        return response()->json(
            $requestQueryFilter->attach(new \App\Domain\Pipeline\Resources\V1\PipelineCollection($queries->pipelineListQuery($request)->get()))
        );
    }

    /**
     * Show a list of existing pipeline entities without opportunity from.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showListOfPipelinesWithoutOpportunityForm(PipelineQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', Pipeline::class);

        return response()->json(
            $queries->pipelineWithoutOpportunityFormListQuery()->get()
        );
    }

    /**
     * Paginate the existing pipeline entities.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginatePipelines(Request $request, PipelineQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Domain\Pipeline\Models\Pipeline::class);

        $pagination = $queries->paginatePipelinesQuery($request)->apiPaginate();

        return \App\Domain\Pipeline\Resources\V1\PaginatedPipeline::collection($pagination);
    }

    /**
     * Batch put pipeline entities.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulkUpdatePipelines(BulkCreateOrUpdatePipelinesRequest $request, \App\Domain\Pipeline\Services\PipelineEntityService $entityService): JsonResponse
    {
        $this->authorize('create', Pipeline::class);

        $resource = $entityService->bulkCreateOrUpdatePipelines($request->getPutPipelineDataCollection());

        return response()->json(
            $resource,
            Response::HTTP_OK,
        );
    }

    /**
     * Store a new pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storePipeline(StorePipelineRequest $request,
                                  \App\Domain\Pipeline\Services\PipelineEntityService $entityService): JsonResponse
    {
        $this->authorize('create', Pipeline::class);

        $pipeline = $entityService->createPipeline($request->getCreatePipelineData());

        return response()->json(
            PipelineWithIncludes::make($pipeline),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update an existing pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updatePipeline(UpdatePipelineRequest $request,
                                   \App\Domain\Pipeline\Services\PipelineEntityService $entityService,
                                   Pipeline $pipeline): JsonResponse
    {
        $this->authorize('update', $pipeline);

        $entityService->updatePipeline($pipeline, $request->getUpdatePipelineData());

        return response()->json(
            PipelineWithIncludes::make($pipeline),
            Response::HTTP_OK
        );
    }

    /**
     * Mark the pipeline entity as default.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markPipelineAsDefault(
        \App\Domain\Pipeline\Services\PipelineEntityService $entityService,
                                          Pipeline $pipeline): Response
    {
        $this->authorize('update', $pipeline);

        $entityService->markPipelineAsDefault($pipeline);

        return response()->noContent();
    }

    /**
     * Delete an existing pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deletePipeline(\App\Domain\Pipeline\Services\PipelineEntityService $entityService, Pipeline $pipeline): JsonResponse
    {
        $this->authorize('delete', $pipeline);

        $entityService->deletePipeline($pipeline);

        return response()->json(
            PipelineWithIncludes::make($pipeline),
            Response::HTTP_OK
        );
    }

    /**
     * Show the specified pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showPipeline(Pipeline $pipeline): JsonResponse
    {
        $this->authorize('view', $pipeline);

        return response()->json(
            PipelineWithIncludes::make($pipeline)
        );
    }

    /**
     * Show the pipeline entity defined as default.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showDefaultPipeline(ShowDefaultPipelineRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            PipelineWithIncludes::make($request->getDefaultPipeline())
        );
    }

    /**
     * Show opportunity form schema of the existing pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showOpportunityFormSchemaOfPipeline(Pipeline $pipeline): JsonResponse
    {
        $this->authorize('viewAny', Pipeline::class);

        return response()->json(
            OpportunityFormSchemaOfPipeline::make($pipeline)
        );
    }

    /**
     * Show opportunity form schema of the default pipeline entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showOpportunityFormSchemaOfDefaultPipeline(PipelineQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            OpportunityFormSchemaOfPipeline::make($queries->defaultPipelinesQuery()->first())
        );
    }
}
