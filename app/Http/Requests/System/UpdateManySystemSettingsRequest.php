<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\{
    SettingValue,
    SettingValueType
};
use Illuminate\Validation\Rule;
use Str;

class UpdateManySystemSettingsRequest extends FormRequest
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $presentSystemSettings;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

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
                Rule::exists('system_settings', 'id')->where('is_read_only', false)
            ],
            '*.value' => [
                'required',
                new SettingValue($this),
                new SettingValueType($this)
            ]
        ];
    }

    public function findPresentSetting(string $attribute)
    {
        $key = Str::before($attribute, '.value');
        $id = $this->input($key.'.id');

        return $this->presentSystemSettings->firstWhere('id', $id);
    }

    public function messages()
    {
        return [
            '*.value.required' => 'Values for the Settings must be present.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->presentSystemSettings = setting()->findMany($this->input('*.id'));
    }
}
