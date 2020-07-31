<?php namespace App\Http\Requests\QuoteTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreHpeContractTemplate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'        => 'required|string|min:2|max:80',
            'company_id'  => 'required|string|uuid|exists:companies,id',
            // 'vendor_id'   => 'required|string|uuid|exists:vendors,id',
            'countries'   => 'required|array',
            'countries.*' => 'required|string|uuid|exists:countries,id',
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'form_data'   => 'array'
        ];
    }
}
