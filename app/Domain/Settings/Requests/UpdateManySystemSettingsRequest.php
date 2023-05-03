<?php

namespace App\Domain\Settings\Requests;

use App\Domain\Settings\Validation\Rules\SettingValue;
use App\Domain\Settings\Validation\Rules\{SettingValueType};
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
                Rule::exists(\App\Domain\Settings\Models\SystemSetting::class, 'id')
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
