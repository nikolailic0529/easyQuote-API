<?php

namespace App\Http\Controllers\API\V1;

use App\DTO\Opportunity\ImportFilesData;
use App\Http\Controllers\Controller;
use App\Services\Opportunity\OpportunityQueryFilterDataProvider;
use App\Http\Requests\Opportunity\{BatchSave,
    CreateOpportunity,
    MarkOpportunityAsLost,
    PaginateOpportunities,
    SetStageOfOpportunity,
    UpdateOpportunity
};
use App\Http\Resources\V1\Appointment\AppointmentListResource;
use App\Http\Resources\V1\Opportunity\GroupedOpportunityCollection;
use App\Http\Resources\V1\Opportunity\OpportunityList;
use App\Http\Resources\V1\Opportunity\OpportunityOfPipelineStageResource;
use App\Http\Resources\V1\Opportunity\OpportunityWithIncludesResource;
use App\Http\Resources\V1\Opportunity\UploadedOpportunities;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Queries\AppointmentQueries;
use App\Queries\OpportunityQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\Opportunity\OpportunityAggregateService;
use App\Services\Opportunity\OpportunityEntityService;
use App\Services\Opportunity\OpportunityImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\{JsonResponse,
    Request,
    Resources\Json\AnonymousResourceCollection,
    Resources\Json\ResourceCollection,
    Response
};
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response as R;
use Throwable;

class OpportunityController extends Controller
{
    /**
     * Show opportunity filters.
     *
     * @param  Request  $request
     * @param  OpportunityQueryFilterDataProvider  $dataProvider
     * @return DataCollection
     * @throws AuthorizationException
     */
    public function showOpportunityFilters(Request $request, OpportunityQueryFilterDataProvider $dataProvider): DataCollection
    {
        $this->authorize('viewAny', Opportunity::class);

        return $dataProvider->getFilters($request);
    }

    /**
     * Paginate existing opportunities with the status 'OK'.
     *
     * @param  PaginateOpportunities  $request
     * @param  OpportunityQueries  $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunities(
        PaginateOpportunities $request,
        OpportunityQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $queries->listOkOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Paginate existing opportunities with the status 'LOST'.
     *
     * @param  PaginateOpportunities  $request
     * @param  OpportunityQueries  $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateLostOpportunities(
        PaginateOpportunities $request,
        OpportunityQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $queries->listLostOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Show opportunity entities grouped by their pipeline stages.
     *
     * @param  Request  $request
     * @param  OpportunityAggregateService  $aggregateService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showOpportunitiesGroupedByPipelineStages(
        Request $request,
        OpportunityAggregateService $aggregateService
    ): JsonResponse {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            GroupedOpportunityCollection::make($aggregateService->getOpportunitiesGroupedByPipelineStages($request)),
            R::HTTP_OK
        );
    }

    /**
     * Paginate opportunities of the pipeline stage.
     *
     * @param  Request  $request
     * @param  OpportunityQueries  $queries
     * @param  PipelineStage  $stage
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunitiesOfPipelineStage(
        Request $request,
        OpportunityAggregateService $aggregateService,
        OpportunityQueries $queries,
        PipelineStage $stage
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Opportunity::class);

        $pagination = $queries->paginateOpportunitiesOfPipelineStageQuery($stage, $request)->apiPaginate();

        $summary = $aggregateService->calculateSummaryOfPipelineStage($stage, $request);

        return OpportunityOfPipelineStageResource::collection($pagination)->additional([
            'meta' => $summary->except('total')
                ->toArray(),
        ]);
    }

    /**
     * Show the specified Opportunity.
     *
     * @param  Opportunity  $opportunity
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
     * @param  CreateOpportunity  $request
     * @param  OpportunityEntityService  $service
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
     * @param  Guard  $guard
     * @param  ImportFilesData  $data
     * @param  OpportunityImportService  $service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function batchUploadOpportunities(
        Request $request,
        ImportFilesData $data,
        OpportunityImportService $service
    ): JsonResponse {
        $this->authorize('create', Opportunity::class);

        $result = $service
            ->setCauser($request->user())
            ->import($data);

        return response()->json(UploadedOpportunities::make($result), R::HTTP_CREATED);
    }

    /**
     * Batch save the uploaded opportunities.
     *
     * @param  BatchSave  $request
     * @param  OpportunityImportService  $service
     * @return Response
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function batchSaveOpportunities(BatchSave $request, OpportunityImportService $service): Response
    {
        $this->authorize('create', Opportunity::class);

        $service
            ->setCauser($request->user())
            ->saveImported($request->getBatchSaveData());

        return response()->noContent();
    }

    /**
     * Update the specified opportunity.
     *
     * @param  UpdateOpportunity  $request
     * @param  Opportunity  $opportunity
     * @param  OpportunityEntityService  $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function updateOpportunity(
        UpdateOpportunity $request,
        OpportunityEntityService $service,
        Opportunity $opportunity
    ): JsonResponse {
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
     * @param  SetStageOfOpportunity  $request
     * @param  OpportunityEntityService  $entityService
     * @param  Opportunity  $opportunity
     * @return ResourceCollection
     * @throws ValidationException
     * @throws Throwable
     */
    public function setStageOfOpportunity(
        SetStageOfOpportunity $request,
        OpportunityEntityService $entityService,
        OpportunityAggregateService $aggregateService,
        Opportunity $opportunity
    ): ResourceCollection {
        $this->authorize('update', $opportunity);

        $entityService
            ->setCauser($request->user())
            ->setStageOfOpportunity(
                $opportunity,
                $request->getSetStageOfOpportunityData(),
            );

        return ResourceCollection::make($aggregateService->calculateSummaryOfDefaultPipeline($request));
    }

    /**
     * Delete the specified opportunity.
     *
     * @param  Request  $request
     * @param  Opportunity  $opportunity
     * @param  OpportunityEntityService  $service
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyOpportunity(
        Request $request,
        OpportunityEntityService $service,
        Opportunity $opportunity
    ): Response {
        $this->authorize('delete', $opportunity);

        $service
            ->setCauser($request->user())
            ->deleteOpportunity($opportunity);

        return response()->noContent();
    }

    /**
     * Mark the specified opportunity entity as lost.
     *
     * @param  MarkOpportunityAsLost  $request
     * @param  Opportunity  $opportunity
     * @param  OpportunityEntityService  $service
     * @return Response
     * @throws AuthorizationException
     */
    public function markOpportunityAsLost(
        MarkOpportunityAsLost $request,
        OpportunityEntityService $service,
        Opportunity $opportunity
    ): Response {
        $this->authorize('update', $opportunity);

        $service
            ->setCauser($request->user())
            ->markOpportunityAsLost($opportunity, $request->getMarkOpportunityAsLostData());

        return response()->noContent();
    }

    /**
     * Mark the specified opportunity as not lost.
     *
     * @param  Request  $request
     * @param  Opportunity  $opportunity
     * @param  OpportunityEntityService  $service
     * @return Response
     * @throws AuthorizationException
     */
    public function markOpportunityAsNotLost(
        Request $request,
        OpportunityEntityService $service,
        Opportunity $opportunity
    ): Response {
        $this->authorize('update', $opportunity);

        $service
            ->setCauser($request->user())
            ->markOpportunityAsNotLost($opportunity);

        return response()->noContent();
    }

    /**
     * List appointments linked to opportunity.
     *
     * @param  Request  $request
     * @param  AppointmentQueries  $appointmentQueries
     * @param  Opportunity  $opportunity
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfOpportunity(
        Request $request,
        AppointmentQueries $appointmentQueries,
        Opportunity $opportunity
    ): AnonymousResourceCollection {
        $this->authorize('view', $opportunity);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($opportunity, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
