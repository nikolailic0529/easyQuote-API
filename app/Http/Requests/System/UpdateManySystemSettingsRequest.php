<?php

namespace App\Http\Requests\System;

use App\Models\System\SystemSetting;
use App\Rules\{SettingValue, SettingValueType};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManySystemSettingsRequest extends FormRequest
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $presentSystemSettings;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            '*.id' => [
                'required',
                'string',
                'uuid',
                Rule::exists(SystemSetting::class, 'id')
                    ->where('is_read_only', false),
            ],
            '*.value' => [
                'required',
                new SettingValue(),
                new SettingValueType(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '*.value.required' => 'Values for the Settings must be present.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->presentSystemSettings = setting()->findMany($this->input('*.id'));
    }
}
