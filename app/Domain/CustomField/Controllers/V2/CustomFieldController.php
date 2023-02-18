<?php

namespace App\Domain\CustomField\Controllers\V2;

use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use App\Domain\CustomField\Queries\CustomFieldQueries;
use App\Domain\CustomField\Requests\EvalCustomFieldValueRequest;
use App\Domain\CustomField\Requests\UpdateCustomFieldValuesRequest;
use App\Domain\CustomField\Resources\V2\CustomFieldValueExtendedResource;
use App\Domain\CustomField\Resources\V2\CustomFieldWithValuesResource;
use App\Domain\CustomField\Services\Calc\CustomFieldEvaluationService;
use App\Domain\CustomField\Services\CustomFieldEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Calculate custom field value.
     */
    public function calculateCustomFieldValue(EvalCustomFieldValueRequest $request,
                                              CustomFieldEvaluationService $service,
                                              CustomField $customField): JsonResponse
    {
        return response()->json(
            $service->evaluate($customField, $request->getExpressionVariables()),
        );
    }

    /**
     * Show custom field by specified name.
     */
    public function showCustomFieldWithValues(Request $request, CustomField $customField): CustomFieldWithValuesResource
    {
        return CustomFieldWithValuesResource::make($customField);
    }

    /**
     * Show extended custom field value.
     *
     * @param CustomFieldValue $customFieldValue
     */
    public function showExtendedCustomField(Request $request,
                                            CustomField $customField,
                                            CustomFieldValue $value): CustomFieldValueExtendedResource
    {
        return CustomFieldValueExtendedResource::make($value);
    }

    /**
     * Update values of the specified custom field.
     *
     * @throws AuthorizationException
     */
    public function updateValuesOfCustomField(UpdateCustomFieldValuesRequest $request,
                                              CustomFieldEntityService $entityService,
                                              CustomField $customField): CustomFieldWithValuesResource
    {
        $this->authorize('viewAny', \App\Domain\Settings\Models\SystemSetting::class);

        $resource = $entityService->updateValuesOfCustomField(
            $customField,
            $request->getUpdateCustomFieldValueCollection()
        );

        return CustomFieldWithValuesResource::make($customField->refresh());
    }
}
