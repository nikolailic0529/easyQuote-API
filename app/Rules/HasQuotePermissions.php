<?php

namespace App\Rules;

use App\Models\{
    Quote\Quote,
    User,
};
use Illuminate\Contracts\Validation\Rule;

class HasQuotePermissions implements Rule
{
    protected Quote $quote;

    protected ?User $user = null;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
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
        $this->user = User::find($value);

        return optional($this->user)->can('update', $this->quote);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return sprintf('User %s does not have Quote permissions.', optional($this->user)->email);
    }
}
