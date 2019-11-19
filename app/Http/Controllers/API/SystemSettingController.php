<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface as SystemSettingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\UpdateManySystemSettingsRequest;
use App\Http\Requests\System\UpdateSystemSettingRequest;
use App\Models\System\SystemSetting;

class SystemSettingController extends Controller
{
    protected $systemSetting;

    public function __construct(SystemSettingRepository $systemSetting)
    {
        $this->systemSetting = $systemSetting;
        $this->authorizeResource(SystemSetting::class, 'setting');
    }

    /**
     * Display a listing of the System Settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            $this->systemSetting->all()
        );
    }

    /**
     * Display the specified System Setting.
     *
     * @param SystemSetting $setting
     * @return \Illuminate\Http\Response
     */
    public function show(SystemSetting $setting)
    {
        return response()->json(
            $this->systemSetting->find($setting->id)
        );
    }

    /**
     * Update the specified System Setting in storage.
     *
     * @param  UpdateSystemSettingRequest  $request
     * @param  \App\Models\System\SystemSetting $setting
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting)
    {
        return response()->json(
            $this->systemSetting->update($request, $setting->id)
        );
    }

    /**
     * Update many System Settings in storage.
     *
     * @param  UpdateManySystemSettingsRequest  $request
     * @param  \App\Models\System\SystemSetting $setting
     * @return \Illuminate\Http\Response
     */
    public function updateMany(UpdateManySystemSettingsRequest $request)
    {
        return response()->json(
            $this->systemSetting->updateMany($request)
        );
    }
}
