<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface as SystemSettingRepository;
use Str;

class UpdateManySystemSettingsRequest extends FormRequest
{
    protected $systemSetting;

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
            '*.id' => 'required|string|uuid|exists:system_settings,id',
            '*.value' => [
                'required',
                function ($attribute, $value, $fail) {
                    $key = Str::before($attribute, '.value');
                    $id = $this->input("{$key}.id");
                    $systemSetting = $this->systemSetting->find($id);

                    if ($systemSetting->is_read_only) {
                        return $fail('You could not to update this setting.');
                    }

                    $possibleValuesString = implode(', ', $systemSetting->flatten_possible_values);

                    !in_array($value, $systemSetting->flatten_possible_values)
                        && $fail("The value for this Setting should be in: {$possibleValuesString}.");
                }
            ]
        ];
    }
}
