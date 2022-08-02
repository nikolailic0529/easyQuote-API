<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunities\BatchUpload;
use App\Http\Requests\Opportunity\{BatchSave,
    CreateOpportunity,
    MarkOpportunityAsLost,
    PaginateOpportunities,
    SetStageOfOpportunity,
    UpdateOpportunity};
use App\Http\Resources\V1\Appointment\AppointmentListResource;
use App\Http\Resources\V1\Opportunity\GroupedOpportunityCollection;
use App\Http\Resources\V1\Opportunity\OpportunityList;
use App\Http\Resources\V1\Opportunity\OpportunityOfStage;
use App\Http\Resources\V1\Opportunity\OpportunityWithIncludesResource;
use App\Http\Resources\V1\Opportunity\UploadedOpportunities;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Queries\AppointmentQueries;
use App\Queries\OpportunityQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\Opportunity\OpportunityAggregateService;
use App\Services\Opportunity\OpportunityEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};
use Symfony\Component\HttpFoundation\Response as R;
use Throwable;

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

        $resource = $queries->paginateOkOpportunitiesQuery($request)->apiPaginate();

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

        $resource = $queries->paginateLostOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Show opportunity entities grouped by their pipeline stages.
     *
     * @param Request $request
     * @param OpportunityAggregateService $aggregateService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showOpportunitiesGroupedByPipelineStages(Request $request, OpportunityAggregateService $aggregateService): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            GroupedOpportunityCollection::make($aggregateService->getOpportunitiesGroupedByPipelineStages($request)),
            R::HTTP_OK
        );
    }

    /**
     * Paginate opportunities of the pipeline stage.
     *
     * @param Request $request
     * @param OpportunityQueries $queries
     * @param PipelineStage $stage
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunitiesOfPipelineStage(Request                     $request,
                                                         OpportunityAggregateService $aggregateService,
                                                         OpportunityQueries          $queries,
                                                         PipelineStage               $stage): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Opportunity::class);

        $pagination = $queries->paginateOpportunitiesOfPipelineStageQuery($stage, $request)->apiPaginate();

        $summary = $aggregateService->calculateSummaryOfPipelineStage($stage, $request);

        return OpportunityOfStage::collection($pagination)->additional(['meta' => $summary->except('total')->toArray()]);
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
            OpportunityWithIncludesResource::make($opportunity),
            R::HTTP_OK
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
     * @throws Throwable
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
            OpportunityWithIncludesResource::make($resource),
            R::HTTP_CREATED
        );
    }

    /**
     * Batch upload the opportunities.
     *
     * @param BatchUpload $request
     * @param OpportunityEntityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws Throwable
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

        return response()->json(UploadedOpportunities::make($result), R::HTTP_CREATED);
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
            ->batchSaveOfImportedOpportunities($request->getBatchSaveData());

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
     * @throws Throwable
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
            OpportunityWithIncludesResource::make($resource),
            R::HTTP_OK
        );
    }

    /**
     * Update stage of the specified opportunity.
     *
     * @param SetStageOfOpportunity $request
     * @param OpportunityEntityService $entityService
     * @param Opportunity $opportunity
     * @return JsonResponse
     * @throws ValidationException
     * @throws Throwable
     */
    public function setStageOfOpportunity(SetStageOfOpportunity    $request,
                                          OpportunityEntityService $entityService,
                                          Opportunity              $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $entityService
            ->setCauser($request->user())
            ->setStageOfOpportunity(
                $opportunity,
                $request->getSetStageOfOpportunityData(),
            );

        return response()->json(status: R::HTTP_NO_CONTENT);
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

    /**
     * List appointments linked to opportunity.
     *
     * @param Request $request
     * @param AppointmentQueries $appointmentQueries
     * @param Opportunity $opportunity
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfOpportunity(Request            $request,
                                                  AppointmentQueries $appointmentQueries,
                                                  Opportunity        $opportunity): AnonymousResourceCollection
    {
        $this->authorize('view', $opportunity);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($opportunity, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
