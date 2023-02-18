<?php

namespace App\Domain\Worldwide\Controllers\V1\Opportunity;

use App\Domain\Appointment\Queries\AppointmentQueries;
use App\Domain\Appointment\Resources\V1\AppointmentListResource;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\ImportFilesData;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Queries\OpportunityQueries;
use App\Domain\Worldwide\Requests\Opportunity\BatchSaveRequest;
use App\Domain\Worldwide\Requests\Opportunity\CreateOpportunityRequest;
use App\Domain\Worldwide\Requests\Opportunity\MarkOpportunityAsLostRequest;
use App\Domain\Worldwide\Requests\Opportunity\PaginateOpportunitiesRequest;
use App\Domain\Worldwide\Requests\Opportunity\SetStageOfOpportunityRequest;
use App\Domain\Worldwide\Requests\Opportunity\ShowOpportunityRequest;
use App\Domain\Worldwide\Requests\Opportunity\UpdateOpportunityRequest;
use App\Domain\Worldwide\Resources\V1\Opportunity\GroupedOpportunityCollection;
use App\Domain\Worldwide\Resources\V1\Opportunity\OpportunityList;
use App\Domain\Worldwide\Resources\V1\Opportunity\OpportunityOfPipelineStageResource;
use App\Domain\Worldwide\Resources\V1\Opportunity\OpportunityWithIncludesResource;
use App\Domain\Worldwide\Resources\V1\Opportunity\UploadedOpportunityCollection;
use App\Domain\Worldwide\Services\Opportunity\OpportunityAggregateService;
use App\Domain\Worldwide\Services\Opportunity\OpportunityEntityService;
use App\Domain\Worldwide\Services\Opportunity\OpportunityImportService;
use App\Domain\Worldwide\Services\Opportunity\OpportunityQueryFilterDataProvider;
use App\Foundation\Http\Controller;
use App\Foundation\Validation\Exceptions\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response as R;

class OpportunityController extends Controller
{
    /**
     * Show opportunity filters.
     *
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
     * @throws AuthorizationException
     */
    public function paginateOpportunities(
        PaginateOpportunitiesRequest $request,
        OpportunityQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $queries->listOkOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Paginate existing opportunities with the status 'LOST'.
     *
     * @throws AuthorizationException
     */
    public function paginateLostOpportunities(
        PaginateOpportunitiesRequest $request,
        OpportunityQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $queries->listLostOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
    }

    /**
     * Show opportunity entities grouped by their pipeline stages.
     *
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
     * @throws AuthorizationException
     */
    public function showOpportunity(ShowOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('view', $opportunity);

        return response()->json(
            OpportunityWithIncludesResource::make($request->loadOpportunity($opportunity)),
            R::HTTP_OK
        );
    }

    /**
     * Create a new opportunity.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function storeOpportunity(CreateOpportunityRequest $request, OpportunityEntityService $service): JsonResponse
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
     * @param Guard $guard
     *
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

        return response()->json(UploadedOpportunityCollection::make($result), R::HTTP_CREATED);
    }

    /**
     * Batch save the uploaded opportunities.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function batchSaveOpportunities(BatchSaveRequest $request, OpportunityImportService $service): Response
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
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function updateOpportunity(
        UpdateOpportunityRequest $request,
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
     * @throws \App\Foundation\Validation\Exceptions\ValidationException
     * @throws \Throwable
     */
    public function setStageOfOpportunity(
        SetStageOfOpportunityRequest $request,
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
     * @throws AuthorizationException
     */
    public function markOpportunityAsLost(
        MarkOpportunityAsLostRequest $request,
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
