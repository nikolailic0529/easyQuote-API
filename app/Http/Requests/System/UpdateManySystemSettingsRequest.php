<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface as SystemSettingRepository;
use Illuminate\Validation\Rule;
use Str;

class UpdateManySystemSettingsRequest extends FormRequest
{
    protected $systemSetting;

    protected static $presentSystemSettings;

    public function __construct(SystemSettingRepository $systemSetting)
    {
        $this->systemSetting = $systemSetting;
    }

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
                function ($attribute, $value, $fail) {
                    $key = Str::before($attribute, '.value');
                    $id = $this->input("{$key}.id");
                    $setting = $this->presentSystemSettings()->firstWhere('id', $id);

                    if (is_null($setting)) {
                        return;
                    }

                    if ($setting->is_read_only) {
                        return $fail(SS_INV_02);
                    }

                    if (is_array($value)) {
                        if (filled(array_diff($value, $setting->flattenPossibleValues))) {
                            return $fail(SS_INV_01);
                        }
                        return;
                    }

                    if (!isset(array_flip($setting->flattenPossibleValues)[$value])) {
                        return $fail(SS_INV_01);
                    }
                }
            ]
        ];
    }

    public function presentSystemSettings()
    {
        if (isset(static::$presentSystemSettings)) {
            return static::$presentSystemSettings;
        }

        return static::$presentSystemSettings = $this->systemSetting->findMany(
            data_get($this->toArray(), '*.id')
        );
    }

    public function messages()
    {
        return [
            '*.value.required' => 'Values for the Settings must be present.'
        ];
    }
}
