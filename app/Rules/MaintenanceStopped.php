<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Facades\Maintenance;

class MaintenanceStopped implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value) {
            return true;
        }

        return Maintenance::stopped();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Maintenance already is running or scheduled. You can not start it now.';
    }
}
