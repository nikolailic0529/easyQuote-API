<?php

namespace App\Domain\Rescue\Validation\Rules;

use App\Domain\Rescue\Models\Quote;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ValidQuoteVersionKey implements Rule
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
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->quote->getKey() === $value) {
            return true;
        }

        return DB::table('quote_versions')->where('quote_id', $this->quote->getKey())->where('id', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid version selected.';
    }
}
