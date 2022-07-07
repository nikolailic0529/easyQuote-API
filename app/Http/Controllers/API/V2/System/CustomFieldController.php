<?php

namespace App\Http\Controllers\API\V2\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\UpdateCustomFieldValues;
use App\Http\Resources\V2\CustomField\CustomFieldValueExtendedResource;
use App\Http\Resources\V2\CustomField\CustomFieldWithValuesResource;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use App\Models\System\SystemSetting;
use App\Queries\CustomFieldQueries;
use App\Services\CustomField\CustomFieldEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * @param Request $request
     * @param CustomField $customField
     * @return CustomFieldWithValuesResource
     */
    public function showCustomFieldWithValues(Request $request, CustomField $customField): CustomFieldWithValuesResource
    {
        return CustomFieldWithValuesResource::make($customField);
    }

    /**
     * Show extended custom field value.
     *
     * @param Request $request
     * @param CustomField $customField
     * @param CustomFieldValue $customFieldValue
     * @return CustomFieldValueExtendedResource
     */
    public function showExtendedCustomField(Request          $request,
                                            CustomField      $customField,
                                            CustomFieldValue $value): CustomFieldValueExtendedResource
    {
        return CustomFieldValueExtendedResource::make($value);
    }

    /**
     * Update values of the specified custom field.
     *
     * @param UpdateCustomFieldValues $request
     * @param CustomFieldEntityService $entityService
     * @param CustomField $customField
     * @return CustomFieldWithValuesResource
     * @throws AuthorizationException
     */
    public function updateValuesOfCustomField(UpdateCustomFieldValues $request,
                                              CustomFieldEntityService $entityService,
                                              CustomField $customField): CustomFieldWithValuesResource
    {
        $this->authorize('viewAny', SystemSetting::class);

        $resource = $entityService->updateValuesOfCustomField(
            $customField,
            $request->getUpdateCustomFieldValueCollection()
        );

        return CustomFieldWithValuesResource::make($customField->refresh());
    }
}