<?php

namespace App\Http\Requests\Quote;

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
            'version_id' => [
                'required',
                'string',
                'uuid',
                Rule::exists('quotes', 'id')->where(fn ($query) => $query->where('is_version', true)->orWhere('id', $this->quote->id))
            ]
        ];
    }
}
