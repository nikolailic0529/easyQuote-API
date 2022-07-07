<?php

namespace App\Http\Controllers\API\V1\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\UpdateCustomFieldValues;
use App\Http\Resources\V1\CustomField\CustomFieldValueResource;
use App\Models\System\CustomField;
use App\Models\System\SystemSetting;
use App\Queries\CustomFieldQueries;
use App\Services\CustomField\CustomFieldEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomFieldController extends Controller
{
    /**
     * Show list of the existing custom fields.
     *
     * @param CustomFieldQueries $queries
     * @return JsonResponse
     */
    public function showListOfCustomFields(CustomFieldQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->customFieldsListingQuery()->get(),
            Response::HTTP_OK
        );
    }

    /**
     * Show custom field by specified name.
     *
     * @param CustomFieldQueries $queries
     * @param CustomField $customField
     * @return JsonResponse
     */
    public function showValuesOfCustomFieldByFieldName(CustomFieldQueries $queries, CustomField $customField): JsonResponse
    {
        return response()->json(
            CustomFieldValueResource::collection($queries->listValuesOfCustomFieldQuery($customField)->get()),
            Response::HTTP_OK
        );
    }

    /**
     * Update values of the specified custom field.
     *
     * @param UpdateCustomFieldValues $request
     * @param CustomFieldEntityService $entityService
     * @param CustomField $customField
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function updateValuesOfCustomField(UpdateCustomFieldValues $request,
                                              CustomFieldEntityService $entityService,
                                              CustomField $customField): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $resource = $entityService->updateValuesOfCustomField(
            $customField,
            $request->getUpdateCustomFieldValueCollection()
        );

        return response()->json(
            CustomFieldValueResource::collection($resource),
            Response::HTTP_OK
        );
    }
}
