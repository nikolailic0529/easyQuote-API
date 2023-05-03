<?php

namespace App\Domain\CustomField\Controllers\V1;

use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Queries\CustomFieldQueries;
use App\Domain\CustomField\Requests\UpdateCustomFieldValuesRequest;
use App\Domain\CustomField\Resources\V1\CustomFieldValueResource;
use App\Domain\CustomField\Services\CustomFieldEntityService;
use App\Domain\Settings\Models\SystemSetting;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomFieldController extends Controller
{
    /**
     * Show list of the existing custom fields.
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
     * @throws AuthorizationException
     */
    public function updateValuesOfCustomField(UpdateCustomFieldValuesRequest $request,
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
