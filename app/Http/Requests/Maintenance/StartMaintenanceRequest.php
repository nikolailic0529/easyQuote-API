<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface as Settings;
use App\Rules\MaintenanceStopped;
use Illuminate\Support\Carbon;

class StartMaintenanceRequest extends FormRequest
{
    public Carbon $carbonStartTime;

    public Carbon $carbonEndTime;

    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'maintenance_message'   => 'nullable|string|max:20000',
            'enable'                => ['nullable', 'boolean', new MaintenanceStopped],
            'start_time'            => 'required|integer|min:1|max:720',
            'end_time'              => 'required|integer|min:1|max:720',
        ];
    }

    protected function passedValidation()
    {
        $this->carbonStartTime = now()->addMinutes((int) $this->start_time);
        $this->carbonEndTime = now()->addMinutes((int) $this->end_time + (int) $this->start_time);
    }

    public function validated()
    {
        return [
            'start_time'    => $this->carbonStartTime,
            'end_time'      => $this->carbonEndTime,
            'enable'        => (bool) $this->enable
        ] + parent::validated();
    }

    public function updateRelatedSettings(): bool
    {
        return $this->settings->updateByKeys([
            'maintenance_start_time' => $this->start_time,
            'maintenance_end_time'   => $this->end_time,
            'maintenance_message'    => $this->maintenance_message
        ]);
    }
}
