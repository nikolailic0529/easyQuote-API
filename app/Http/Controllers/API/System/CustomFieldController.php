<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\UpdateCustomFieldValues;
use App\Models\System\SystemSetting;
use App\Queries\CustomFieldQueries;
use App\Services\CustomField\CustomFieldEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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
     * @param string $customFieldName
     * @param CustomFieldQueries $queries
     * @return JsonResponse
     */
    public function showValuesOfCustomFieldByFieldName(string $customFieldName, CustomFieldQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->customFieldValuesByFieldNameQuery($customFieldName)->get(),
            Response::HTTP_OK
        );
    }

    /**
     * Update values of the specified custom field.
     *
     * @param UpdateCustomFieldValues $request
     * @param CustomFieldEntityService $entityService
     * @param string $customFieldName
     * @return Response
     * @throws AuthorizationException
     */
    public function updateValuesOfCustomField(UpdateCustomFieldValues $request,
                                              CustomFieldEntityService $entityService,
                                              string $customFieldName): Response
    {
        $this->authorize('viewAny', SystemSetting::class);

        $entityService->updateValuesOfCustomField(
            $request->getCustomFieldModel(),
            $request->getUpdateCustomFieldValueCollection()
        );

        return response()->noContent();
    }
}
