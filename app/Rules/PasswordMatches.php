<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PasswordMatches implements Rule
{
    /** Username field */
    protected string $username;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $username = 'email')
    {
        $this->username = $username;
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
        return Auth::validate([
            $this->username => request()->input($this->username),
            'password' => $value
        ]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Password does not match with our records.';
    }
}
