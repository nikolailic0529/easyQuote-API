<?php

namespace App\Domain\Country\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'iso_3166_2' => ['required', 'string', 'max:50', Rule::unique('countries')->whereNull('deleted_at')->ignore($this->country)],
            'default_currency_id' => ['nullable', 'string', 'uuid', Rule::exists('currencies', 'id')],
            'currency_code' => 'nullable|string|max:10',
            'currency_name' => 'nullable|string|max:50',
            'currency_symbol' => 'nullable|string|max:10',
        ];
    }

    public function messages()
    {
        return [
            'iso_3166_2.unique' => 'The given ISO Code has already taken.',
        ];
    }
}
