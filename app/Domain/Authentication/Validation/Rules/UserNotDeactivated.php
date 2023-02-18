<?php

namespace App\Domain\Authentication\Validation\Rules;

use App\Domain\User\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UserNotDeactivated implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

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
        return User::whereEmail($value)->whereNull('activated_at')->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The user is blocked. Please contact administrator.';
    }
}
