<?php

namespace App\Http\Controllers\API\V1\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\{OpportunityForm\StoreOpportunityForm,
    OpportunityForm\UpdateOpportunityForm,
    OpportunityForm\UpdateSchemaOfOpportunityForm};
use App\Http\Resources\V1\OpportunityForm\OpportunityFormWithIncludes;
use App\Http\Resources\V1\OpportunityForm\PaginatedOpportunityForm;
use App\Models\OpportunityForm\OpportunityForm;
use App\Queries\OpportunityFormQueries;
use App\Services\OpportunityForm\OpportunityFormEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OpportunityFormController extends Controller
{
    /**
     * Paginate the existing opportunity form entities.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Queries\OpportunityFormQueries $queries
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginateOpportunityForms(Request $request, OpportunityFormQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OpportunityForm::class);

        $pagination = $queries->paginateOpportunityFormsQuery($request)->apiPaginate();

        return PaginatedOpportunityForm::collection($pagination);
    }


    /**
     * Show the specified opportunity form entity.
     *
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showOpportunityForm(OpportunityForm $opportunityForm): JsonResponse
    {
        $this->authorize('view', $opportunityForm);

        return response()->json(
            OpportunityFormWithIncludes::make($opportunityForm),
            Response::HTTP_OK
        );
    }

    /**
     * Store a new opportunity form entity.
     *
     * @param \App\Http\Requests\OpportunityForm\StoreOpportunityForm $request
     * @param \App\Services\OpportunityForm\OpportunityFormEntityService $entityService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeOpportunityForm(StoreOpportunityForm $request,
                                         OpportunityFormEntityService $entityService): JsonResponse
    {
        $this->authorize('create', OpportunityForm::class);

        $resource = $entityService->createOpportunityForm($request->getCreateOpportunityFormData());

        return response()->json(
            OpportunityFormWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update opportunity form entity.
     *
     * @param \App\Http\Requests\OpportunityForm\UpdateOpportunityForm $request
     * @param \App\Services\OpportunityForm\OpportunityFormEntityService $entityService
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateOpportunityForm(UpdateOpportunityForm $request,
                                          OpportunityFormEntityService $entityService,
                                          OpportunityForm $opportunityForm): JsonResponse
    {
        $this->authorize('update', $opportunityForm);

        $resource = $entityService->updateOpportunityForm($opportunityForm, $request->getUpdateOpportunityFormData());

        return response()->json(
            OpportunityFormWithIncludes::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Update schema of the specified opportunity form entity.
     *
     * @param \App\Http\Requests\OpportunityForm\UpdateSchemaOfOpportunityForm $request
     * @param \App\Services\OpportunityForm\OpportunityFormEntityService $entityService
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSchemaOfOpportunityForm(UpdateSchemaOfOpportunityForm $request,
                                                  OpportunityFormEntityService $entityService,
                                                  OpportunityForm $opportunityForm): Response
    {
        $this->authorize('update', $opportunityForm);

        $entityService->updateSchemaOfOpportunityForm($opportunityForm, $request->getFormSchema());

        return response()->noContent();
    }

    /**
     * Delete the specified opportunity form entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteOpportunityForm(OpportunityFormEntityService $entityService, OpportunityForm $opportunityForm)
    {
        $this->authorize('delete', $opportunityForm);

        $entityService->deleteOpportunityForm($opportunityForm);

        return response()->noContent();
    }
}
