<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RequiredIfCsv implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if(!request()->filled('quote_file')) {
            return true;
        }

        $file = request()->file('quote_file');
        
        $extension = strtolower($file->extension());
                
        if(!in_array($extension, ['txt', 'csv'])) {
            return true;
        }

        return (Boolean) $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is required for this file format.';
    }
}
