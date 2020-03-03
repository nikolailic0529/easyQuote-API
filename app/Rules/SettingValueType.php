<?php

namespace App\Rules;

use App\Http\Requests\System\UpdateManySystemSettingsRequest;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class SettingValueType implements Rule
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

        if (!is_null($setting->possible_values)) {
            return true;
        }

        $rule = $this->translateTypeRule($setting->type);

        $validator = Validator::make(compact('value'), ['value' => $rule]);

        return $validator->passes();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The given Setting value is invalid.';
    }

    protected function translateTypeRule(string $type)
    {
        switch ($type) {
            case 'float':
            case 'integer':
                return ['min:0', 'numeric'];
                break;
        }
    }
}
