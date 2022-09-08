<?php

namespace App\Http\Controllers\API\V1\System;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface as SystemSettingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\{UpdateManySystemSettingsRequest, UpdateSystemSettingRequest};
use App\Http\Resources\V1\Setting\PublicSettingCollection;
use App\Http\Resources\V1\Setting\SettingCollection;
use App\Models\System\SystemSetting;
use App\Queries\SystemSettingQueries;
use App\Services\Settings\DynamicSettingsProviders\DynamicSettingsProviderCollection;
use App\Services\Settings\SettingsDataProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function __construct(protected SystemSettingRepository $systemSetting)
    {
        $this->authorizeResource(SystemSetting::class, 'setting');
    }

    /**
     * List system settings.
     *
     * @param  SystemSettingQueries  $queries
     * @param  SettingsDataProviderService  $service
     * @param  DynamicSettingsProviderCollection  $dynamicSettings
     * @return JsonResponse
     */
    public function index(
        SystemSettingQueries $queries,
        SettingsDataProviderService $service,
        DynamicSettingsProviderCollection $dynamicSettings
    ): JsonResponse {
        $collection = SettingCollection::make(
            $service->hydratePossibleValuesOfSettings(
                $queries->listSystemSettingsQuery()->get()
            )
            ->merge($dynamicSettings->toCollection())
        );

        return response()->json($collection);
    }

    /**
     * List exposed system settings.
     *
     * @param  Request  $request
     * @param  SystemSettingQueries  $queries
     * @return JsonResponse
     */
    public function showPublicSettings(Request $request, SystemSettingQueries $queries): JsonResponse
    {
        $collection = PublicSettingCollection::make(
            $queries->listPublicSystemSettingsQuery()->get()
        );

        return response()->json($collection);
    }

    /**
     * Show system setting.
     *
     * @param  SystemSetting  $setting
     * @return JsonResponse
     */
    public function show(SystemSetting $setting): JsonResponse
    {
        return response()->json($setting);
    }

    /**
     * Update system setting.
     *
     * @param  UpdateSystemSettingRequest  $request
     * @param  SystemSetting  $setting
     * @return JsonResponse
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting): JsonResponse
    {
        return response()->json(
            $this->systemSetting->update($request, $setting->getKey())
        );
    }

    /**
     * Bulk update system settings.
     *
     * @param  UpdateManySystemSettingsRequest  $request
     * @return JsonResponse
     */
    public function updateMany(UpdateManySystemSettingsRequest $request): JsonResponse
    {
        return response()->json(
            $this->systemSetting->updateMany($request)
        );
    }
}
