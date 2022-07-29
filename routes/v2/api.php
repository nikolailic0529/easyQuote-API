<?php

use App\Http\Controllers\API\V2\System\CustomFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(static function (): void {
    Route::get('custom-fields', [CustomFieldController::class, 'showListOfCustomFields']);
    Route::get('custom-field-values/{custom_field:field_name}', [CustomFieldController::class, 'showCustomFieldWithValues']);
    Route::put('custom-field-values/{custom_field:field_name}', [CustomFieldController::class, 'updateValuesOfCustomField']);
    Route::get('custom-field-values/{custom_field:field_name}/{value:id}', [CustomFieldController::class, 'showExtendedCustomField']);

    Route::post('custom-fields/{custom_field:field_name}/calculate', [CustomFieldController::class, 'calculateCustomFieldValue']);
});