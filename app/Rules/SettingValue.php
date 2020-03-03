<?php

namespace App\Rules;

use App\Http\Requests\System\UpdateManySystemSettingsRequest;
use Illuminate\Contracts\Validation\Rule;

class SettingValue implements Rule
{
    /** @var \App\Http\Requests\System\UpdateManySystemSettingsRequest */
    protected UpdateManySystemSettingsRequest $request;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(UpdateManySystemSettingsRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $setting = $this->request->findPresentSetting($attribute);

        if (is_null($setting) || $setting->is_read_only) {
            return false;
        }

        if (is_null($setting->possibleValues)) {
            return true;
        }

        if (is_array($value)) {
            return blank(array_diff($value, $setting->flattenPossibleValues));
        }

        return isset(array_flip($setting->flattenPossibleValues)[$value]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return SS_INV_01;
    }
}
