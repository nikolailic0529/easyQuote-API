<?php

namespace App\Rules;

use App\Models\Quote\Quote;
use Illuminate\Contracts\Validation\Rule;

class UseRowGroups implements Rule
{
    protected Quote $quote;

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
        if (request()->missing('quote_id') || !$value) {
            return true;
        }

        return !empty(($this->quote->activeVersion ?? $this->quote)->selected_group_description_names);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'At least one group should be selected.';
    }
}
