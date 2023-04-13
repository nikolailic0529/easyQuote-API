<?php

namespace App\Domain\Maintenance\Requests;

use App\Domain\Maintenance\Validation\Rules\MaintenanceStopped;
use App\Domain\Settings\Contracts\SystemSettingRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StartMaintenanceRequest extends FormRequest
{
    public Carbon $carbonStartTime;
    public Carbon $carbonEndTime;

    public function rules(): array
    {
        return [
            'maintenance_message' => ['nullable', 'string', 'max:20000'],
            'enable' => ['nullable', 'boolean', new MaintenanceStopped()],
            'start_time' => ['required', 'integer', 'min:1', 'max:720'],
            'end_time' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->carbonStartTime = now()->addMinutes((int) $this->input('start_time'));
        $this->carbonEndTime = now()->addMinutes((int) $this->input('end_time') + (int) $this->input('start_time'));
    }

    public function validated(): array
    {
        return [
                'start_time' => $this->carbonStartTime,
                'end_time' => $this->carbonEndTime,
                'enable' => $this->boolean('enable'),
            ] + parent::validated();
    }

    public function updateRelatedSettings(): bool
    {
        $repository = $this->container->make(SystemSettingRepositoryInterface::class);

        return $repository->updateByKeys([
            'maintenance_start_time' => $this->input('start_time'),
            'maintenance_end_time' => $this->input('end_time'),
            'maintenance_message' => $this->input('maintenance_message'),
        ]);
    }
}
