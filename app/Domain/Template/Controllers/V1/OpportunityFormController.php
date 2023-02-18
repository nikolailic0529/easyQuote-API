<?php

namespace App\Domain\Template\Controllers\V1;

use App\Domain\Template\Queries\OpportunityFormQueries;
use App\Domain\Template\Requests\OpportunityForm\StoreOpportunityFormRequest;
use App\Domain\Template\Requests\OpportunityForm\UpdateOpportunityFormRequest;
use App\Domain\Template\Requests\OpportunityForm\UpdateSchemaOfOpportunityFormRequest;
use App\Domain\Template\Resources\V1\OpportunityForm\OpportunityFormWithIncludesResource;
use App\Domain\Template\Resources\V1\OpportunityForm\PaginatedOpportunityForm;
use App\Domain\Template\Services\OpportunityFormEntityService;
use App\Domain\Worldwide\Models\OpportunityForm;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OpportunityFormController extends Controller
{
    /**
     * Paginate the existing opportunity form entities.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function paginateOpportunityForms(
        Request $request,
        OpportunityFormQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', OpportunityForm::class);

        $pagination = $queries->paginateOpportunityFormsQuery($request)->apiPaginate();

        return PaginatedOpportunityForm::collection($pagination);
    }

    /**
     * Show the specified opportunity form entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showOpportunityForm(OpportunityForm $opportunityForm): JsonResponse
    {
        $this->authorize('view', $opportunityForm);

        return response()->json(
            OpportunityFormWithIncludesResource::make($opportunityForm),
            Response::HTTP_OK
        );
    }

    /**
     * Store a new opportunity form entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeOpportunityForm(
        StoreOpportunityFormRequest $request,
        OpportunityFormEntityService $entityService
    ): JsonResponse {
        $this->authorize('create', OpportunityForm::class);

        $resource = $entityService->createOpportunityForm($request->getCreateOpportunityFormData());

        return response()->json(
            OpportunityFormWithIncludesResource::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Copy the opportunity form.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function copyOpportunityForm(
        StoreOpportunityFormRequest $request,
        OpportunityFormEntityService $entityService,
        OpportunityForm $opportunityForm,
    ): JsonResponse {
        $this->authorize('create', OpportunityForm::class);

        $resource = $entityService->replicateOpportunityForm($request->getCreateOpportunityFormData(),
            $opportunityForm);

        return response()->json(
            OpportunityFormWithIncludesResource::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update opportunity form entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateOpportunityForm(
        UpdateOpportunityFormRequest $request,
        OpportunityFormEntityService $entityService,
        OpportunityForm $opportunityForm
    ): JsonResponse {
        $this->authorize('update', $opportunityForm);

        $resource = $entityService->updateOpportunityForm($opportunityForm, $request->getUpdateOpportunityFormData());

        return response()->json(
            OpportunityFormWithIncludesResource::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Update schema of the specified opportunity form entity.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSchemaOfOpportunityForm(
        UpdateSchemaOfOpportunityFormRequest $request,
        OpportunityFormEntityService $entityService,
        OpportunityForm $opportunityForm
    ): Response {
        $this->authorize('update', $opportunityForm);

        $entityService->updateSchemaOfOpportunityForm($opportunityForm, $request->getUpdateSchemaData());

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
