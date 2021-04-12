<?php

namespace App\Http\Controllers\API\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityTemplate\UpdateOpportunityTemplate;
use App\Models\Opportunity;
use App\Services\Opportunity\OpportunityTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OpportunityTemplateController extends Controller
{
    /**
     * Show Opportunity template schema.
     *
     * @param OpportunityTemplateService $service
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showOpportunityTemplate(OpportunityTemplateService $service): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        return response()->json(
            $service->getOpportunityTemplateSchema(),
            Response::HTTP_OK
        );
    }

    /**
     * Update Opportunity template schema.
     *
     * @param UpdateOpportunityTemplate $request
     * @param OpportunityTemplateService $service
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateOpportunityTemplate(UpdateOpportunityTemplate $request, OpportunityTemplateService $service): Response
    {
        $this->authorize('update_opportunity_form_template');

        $service->updateOpportunityTemplateSchema($request->getTemplateSchema());

        return response()->noContent();
    }
}
