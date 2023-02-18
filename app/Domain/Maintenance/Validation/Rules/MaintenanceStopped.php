<?php

namespace App\Domain\Maintenance\Validation\Rules;

use App\Domain\Maintenance\Facades\Maintenance;
use Illuminate\Contracts\Validation\Rule;

class MaintenanceStopped implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
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
