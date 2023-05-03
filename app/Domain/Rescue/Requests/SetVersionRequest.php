<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Rescue\Validation\Rules\ValidQuoteVersionKey;
use Illuminate\Foundation\Http\FormRequest;

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
            'version_id' => ['bail', 'required', 'uuid', new ValidQuoteVersionKey($this->quote)],
        ];
    }
}
