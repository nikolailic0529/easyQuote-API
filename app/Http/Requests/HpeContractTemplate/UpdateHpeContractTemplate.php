<?php

namespace App\Http\Requests\HpeContractTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Company;
use App\Models\Vendor;
use App\Models\Template\HpeContractTemplate;

class UpdateHpeContractTemplate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|min:2|max:80',
            'company_id'  => ['uuid', Rule::exists(Company::class, 'id')->whereNull('deleted_at')],
            'vendor_id'   => ['uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'countries' => 'array',
            'countries.*' => 'string|uuid|exists:countries,id',
            'form_data' => 'array',
            'data_headers' => 'nullable|array',
            'data_headers.*.key' => [
                'required',
                'string',
                Rule::in(HpeContractTemplate::dataHeaderKeys())
            ],
            'data_headers.*.value' => [
                'required',
                'string',
                'min:1'
            ],
            'complete_design' => 'boolean'
        ];
    }
}
