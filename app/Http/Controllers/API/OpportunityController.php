<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunities\BatchUpload;
use App\Http\Resources\Opportunity\UploadedOpportunities;
use App\Http\Requests\Opportunity\{BatchSave,
    CreateOpportunity,
    MarkOpportunityAsLost,
    PaginateOpportunities,
    UpdateOpportunity};
use App\Http\Resources\Opportunity\OpportunityList;
use App\Http\Resources\Opportunity\OpportunityWithIncludes;
use App\Models\Opportunity;
use App\Queries\OpportunityQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\Opportunity\OpportunityAggregateService;
use App\Services\Opportunity\OpportunityEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};

class OpportunityController extends Controller
{
    /**
     * Paginate existing opportunities with the status 'OK'.
     *
     * @param PaginateOpportunities $request
     * @param OpportunityQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunities(PaginateOpportunities $request, OpportunityQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $request->transformOpportunitiesQuery($queries->paginateOkOpportunitiesQuery($request))->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Paginate existing opportunities with the status 'LOST'.
     *
     * @param PaginateOpportunities $request
     * @param OpportunityQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateLostOpportunities(PaginateOpportunities $request, OpportunityQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $request->transformOpportunitiesQuery($queries->paginateLostOpportunitiesQuery($request))->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Show opportunity entities grouped by their pipeline stages.
     *
     * @param OpportunityAggregateService $aggregateService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showOpportunitiesGroupedByPipelineStages(OpportunityAggregateService $aggregateService): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            $aggregateService->getOpportunitiesGroupedByPipelineStages(),
            Response::HTTP_OK
        );
    }

    /**
     * Show the specified Opportunity.
     *
     * @param Opportunity $opportunity
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showOpportunity(Opportunity $opportunity): JsonResponse
    {
        $this->authorize('view', $opportunity);

        return response()->json(
            OpportunityWithIncludes::make($opportunity),
            Response::HTTP_OK
        );
    }

    /**
     * Create a new opportunity.
     *
     * @param CreateOpportunity $request
     * @param OpportunityEntityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function storeOpportunity(CreateOpportunity $request, OpportunityEntityService $service): JsonResponse
    {
        $this->authorize('create', Opportunity::class);

        $resource = $service
            ->setCauser($request->user())
            ->createOpportunity(
                $request->getOpportunityData()
            );

        return response()->json(
            OpportunityWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Batch upload the opportunities.
     *
     * @param BatchUpload $request
     * @param OpportunityEntityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws \Throwable
     */
    public function batchUploadOpportunities(BatchUpload $request, OpportunityEntityService $service): JsonResponse
    {
        $this->authorize('create', Opportunity::class);

        $result = $service
            ->setCauser($request->user())
            ->batchImportOpportunities(
            $request->getImportOpportunityData(),
            $request->user()
        );

        return response()->json(UploadedOpportunities::make($result), Response::HTTP_CREATED);
    }

    /**
     * Batch save the uploaded opportunities.
     *
     * @param BatchSave $request
     * @param OpportunityEntityService $service
     * @return Response
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function batchSaveOpportunities(BatchSave $request, OpportunityEntityService $service): Response
    {
        $this->authorize('create', Opportunity::class);

        $service
            ->setCauser($request->user())
            ->batchSaveOpportunities($request->getBatchSaveData());

        return response()->noContent();
    }

    /**
     * Update the specified opportunity.
     *
     * @param UpdateOpportunity $request
     * @param Opportunity $opportunity
     * @param OpportunityEntityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function updateOpportunity(UpdateOpportunity        $request,
                                      OpportunityEntityService $service,
                                      Opportunity              $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $resource = $service
            ->setCauser($request->user())
            ->updateOpportunity(
                $opportunity,
                $request->getOpportunityData()
            );

        return response()->json(
            OpportunityWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified opportunity.
     *
     * @param Request $request
     * @param Opportunity $opportunity
     * @param OpportunityEntityService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyOpportunity(Request                  $request,
                                       OpportunityEntityService $service,
                                       Opportunity              $opportunity): Response
    {
        $this->authorize('delete', $opportunity);

        $service
            ->setCauser($request->user())
            ->deleteOpportunity($opportunity);

        return response()->noContent();
    }

    /**
     * Mark the specified opportunity entity as lost.
     *
     * @param MarkOpportunityAsLost $request
     * @param Opportunity $opportunity
     * @param OpportunityEntityService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function markOpportunityAsLost(MarkOpportunityAsLost    $request,
                                          OpportunityEntityService $service,
                                          Opportunity              $opportunity): Response
    {
        $this->authorize('update', $opportunity);

        $service
            ->setCauser($request->user())
            ->markOpportunityAsLost($opportunity, $request->getMarkOpportunityAsLostData());

        return response()->noContent();
    }

    /**
     * Mark the specified opportunity as not lost.
     *
     * @param Request $request
     * @param Opportunity $opportunity
     * @param OpportunityEntityService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function markOpportunityAsNotLost(Request                  $request,
                                             OpportunityEntityService $service,
                                             Opportunity              $opportunity): Response
    {
        $this->authorize('update', $opportunity);

        $service
            ->setCauser($request->user())
            ->markOpportunityAsNotLost($opportunity);

        return response()->noContent();
    }
}
