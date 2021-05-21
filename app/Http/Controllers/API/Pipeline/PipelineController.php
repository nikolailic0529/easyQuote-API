<?php

namespace App\Http\Controllers\API\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityTemplate\UpdateOpportunityTemplate;
use App\Http\Requests\Pipeline\StorePipeline;
use App\Http\Requests\Pipeline\UpdatePipeline;
use App\Http\Resources\{Pipeline\OpportunityFormSchemaOfPipeline,
    Pipeline\PaginatedPipeline,
    Pipeline\PipelineState,
    Pipeline\PipelineWithIncludes};
use App\Models\Opportunity;
use App\Models\Pipeline\Pipeline;
use App\Queries\PipelineQueries;
use App\Services\Pipeline\PipelineEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PipelineController extends Controller
{
    /**
     * Show a list of existing pipelines.
     *
     * @param \App\Queries\PipelineQueries $queries
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showListOfPipelines(PipelineQueries $queries): JsonResponse
    {
        $this->authorize('viewAny', Pipeline::class);

        return response()->json(
            $queries->pipelineListQuery()->get()
        );
    }

    /**
     * Paginate the existing pipeline entities.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Queries\PipelineQueries $queries
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginatePipelines(Request $request, PipelineQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Pipeline::class);

        $pagination = $queries->paginatePipelinesQuery($request)->apiPaginate();

        return PaginatedPipeline::collection($pagination);
    }

    /**
     * Store a new pipeline entity.
     *
     * @param \App\Http\Requests\Pipeline\StorePipeline $request
     * @param \App\Services\Pipeline\PipelineEntityService $entityService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storePipeline(StorePipeline $request,
                                  PipelineEntityService $entityService): JsonResponse
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
     * @param \App\Http\Requests\Pipeline\UpdatePipeline $request
     * @param \App\Services\Pipeline\PipelineEntityService $entityService
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updatePipeline(UpdatePipeline $request,
                                   PipelineEntityService $entityService,
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
     * @param \App\Services\Pipeline\PipelineEntityService $entityService
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markPipelineAsDefault(PipelineEntityService $entityService,
                                          Pipeline $pipeline): Response
    {
        $this->authorize('update', $pipeline);

        $entityService->markPipelineAsDefault($pipeline);

        return response()->noContent();
    }

    /**
     * Delete an existing pipeline entity.
     *
     * @param \App\Services\Pipeline\PipelineEntityService $entityService
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deletePipeline(PipelineEntityService $entityService, Pipeline $pipeline): JsonResponse
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
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\JsonResponse
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
     * Show opportunity form schema of the existing pipeline entity.
     *
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\JsonResponse
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
        $this->authorize('viewAny', Pipeline::class);

        return response()->json(
          OpportunityFormSchemaOfPipeline::make($queries->defaultPipelinesQuery()->first())
        );
    }

    /**
     * Update opportunity from schema of the specified pipeline entity.
     *
     * @param \App\Http\Requests\OpportunityTemplate\UpdateOpportunityTemplate $request
     * @param \App\Services\Pipeline\PipelineEntityService $entityService
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateOpportunityFormSchemaOfPipeline(UpdateOpportunityTemplate $request,
                                                          PipelineEntityService $entityService,
                                                          Pipeline $pipeline): \Illuminate\Http\Response
    {

        $this->authorize('update', $pipeline);

        $entityService->updateOpportunityFormSchemaOfPipeline($pipeline, $request->getTemplateSchema());

        return response()->noContent();
    }
}
