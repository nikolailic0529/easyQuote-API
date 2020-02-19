<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StartMaintenanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'maintenance_message'   => 'nullable|string|max:20000',
            'start_time'            => ['required', 'date_format:' . config('date.format_ui_time'), 'after:now'],
            'end_time'              => ['required', 'date_format:' . config('date.format_ui_time'), 'after:start_time'],
        ];
    }

    public function startTime(): Carbon
    {
        return Carbon::createFromFormat(config('date.format_ui_time'), $this->start_time);
    }

    public function endTime(): Carbon
    {
        return Carbon::createFromFormat(config('date.format_ui_time'), $this->end_time);
    }
}
