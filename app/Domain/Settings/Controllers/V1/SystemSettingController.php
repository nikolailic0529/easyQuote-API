<?php

namespace App\Domain\Settings\Controllers\V1;

use App\Domain\Settings\Contracts\SystemSettingRepositoryInterface as SystemSettingRepository;
use App\Domain\Settings\DynamicSettings\DynamicSettingsProviderCollection;
use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Settings\Queries\SystemSettingQueries;
use App\Domain\Settings\Requests\UpdateManySystemSettingsRequest;
use App\Domain\Settings\Requests\{UpdateSystemSettingRequest};
use App\Domain\Settings\Resources\V1\PublicSettingCollection;
use App\Domain\Settings\Resources\V1\SettingCollection;
use App\Domain\Settings\Services\SettingsDataProviderService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function __construct(protected SystemSettingRepository $systemSetting)
    {
        $this->authorizeResource(\App\Domain\Settings\Models\SystemSetting::class, 'setting');
    }

    /**
     * List system settings.
     *
     * @param \App\Domain\Settings\DynamicSettingsProviders\DynamicSettingsProviderCollection $dynamicSettings
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
     */
    public function show(SystemSetting $setting): JsonResponse
    {
        return response()->json($setting);
    }

    /**
     * Update system setting.
     *
     * @param \App\Domain\Settings\Requests\UpdateSystemSettingRequest $request
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting): JsonResponse
    {
        return response()->json(
            $this->systemSetting->update($request, $setting->getKey())
        );
    }

    /**
     * Bulk update system settings.
     */
    public function updateMany(UpdateManySystemSettingsRequest $request): JsonResponse
    {
        return response()->json(
            $this->systemSetting->updateMany($request)
        );
    }
}
