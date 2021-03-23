<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunities\BatchUpload;
use App\Http\Resources\Opportunity\CreatedOpportunity;
use App\Services\Opportunity\OpportunityBatchFileReader;
use App\Http\Requests\Opportunity\{BatchSave, CreateOpportunity, UpdateOpportunity};
use App\Http\Resources\Opportunity\OpportunityList;
use App\Models\Opportunity;
use App\Queries\OpportunityQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\Opportunity\OpportunityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};

class OpportunityController extends Controller
{
    /**
     * Paginate existing opportunities.
     *
     * @param Request $request
     * @param OpportunityQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunities(Request $request, OpportunityQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Opportunity::class);

        $resource = $queries->paginateOpportunitiesQuery($request)->apiPaginate();

        return OpportunityList::collection($resource);
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
            CreatedOpportunity::make($opportunity),
            Response::HTTP_OK
        );
    }

    /**
     * Create a new opportunity.
     *
     * @param CreateOpportunity $request
     * @param OpportunityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function storeOpportunity(CreateOpportunity $request, OpportunityService $service): JsonResponse
    {
        $this->authorize('create', Opportunity::class);

        $resource = $service->createOpportunity(
            $request->getOpportunityData()
        );

        return response()->json(
            CreatedOpportunity::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Batch upload the opportunities.
     *
     * @param BatchUpload $request
     * @param OpportunityService $service
     * @return JsonResponse
     */
    public function batchUploadOpportunities(BatchUpload $request, OpportunityService $service): JsonResponse
    {
        $result = $service->batchImportOpportunities($request->file('file'), $request->user());

        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Batch save the uploaded opportunities.
     *
     * @param BatchSave $request
     * @param OpportunityService $service
     * @return Response
     * @throws ValidationException
     */
    public function batchSaveOpportunities(BatchSave $request, OpportunityService $service): Response
    {
        $service->batchSaveOpportunities($request->getBatchSaveData());

        return response()->noContent();
    }

    /**
     * Update the specified opportunity.
     *
     * @param UpdateOpportunity $request
     * @param Opportunity $opportunity
     * @param OpportunityService $service
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function updateOpportunity(UpdateOpportunity $request, Opportunity $opportunity, OpportunityService $service): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $resource = $service->updateOpportunity(
            $opportunity,
            $request->getOpportunityData()
        );

        return response()->json(
            CreatedOpportunity::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified opportunity.
     *
     * @param Opportunity $opportunity
     * @param OpportunityService $service
     * @return Response
     */
    public function destroyOpportunity(Opportunity $opportunity, OpportunityService $service): Response
    {
        $service->deleteOpportunity($opportunity);

        return response()->noContent();
    }
}
