<?php

namespace App\Http\Requests\Country;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
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
            'iso_3166_2' => ['required', 'string', 'max:2', Rule::unique('countries', 'iso_3166_2')->whereNull('deleted_at')],
            'default_currency_id' => ['nullable', 'string', 'uuid', Rule::exists('currencies', 'id')],
            'currency_code' => 'nullable|string|max:10',
            'currency_name' => 'nullable|string|max:50',
            'currency_symbol' => 'nullable|string|max:10'
        ];
    }
}
