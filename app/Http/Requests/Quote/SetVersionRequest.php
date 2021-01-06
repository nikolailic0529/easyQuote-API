<?php

namespace App\Http\Requests\Quote;

use App\Rules\ValidQuoteVersionKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetVersionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'version_id' => ['bail', 'required', 'uuid', (new ValidQuoteVersionKey($this->quote))],
        ];
    }
}
