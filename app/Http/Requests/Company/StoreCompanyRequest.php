<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:191',
                'min:2',
                Rule::unique(Company::class)->whereNull('deleted_at')
            ],
            'vat' => [
                'nullable',
                'string',
                'max:191',
                'min:2',
                Rule::unique(Company::class)->whereNull('deleted_at')
            ],
            'short_code' => [
                Rule::requiredIf(fn () => $this->type === Company::INT_TYPE),
                'string',
                'size:3',
                Rule::unique(Company::class)->whereNull('deleted_at')
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Company::TYPES)
            ],
            'logo' => [
                'image',
                'max:2048'
            ],
            'category' => [
                'nullable',
                Rule::requiredIf(fn () => $this->type === Company::EXT_TYPE),
                'string',
                Rule::in(Company::CATEGORIES)
            ],
            'email' => 'required|email',
            'phone' => 'nullable|string|min:4|phone',
            'website' => 'nullable|string|min:4',
            'vendors' => 'array',
            'vendors.*' => 'required|uuid|exists:vendors,id',
            'default_vendor_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::in($this->vendors)
            ],
            'default_country_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_vendor', 'country_id')->where('vendor_id', $this->default_vendor_id)
            ],
            'default_template_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_quote_template', 'quote_template_id')->where('country_id', $this->default_country_id)
            ],

            'addresses_attach' => 'nullable|array',
            'addresses_attach.*' => 'required|string|uuid|exists:addresses,id',
            'addresses_detach' => 'nullable|array',
            'addresses_detach.*' => 'required|string|uuid|exists:addresses,id',

            'contacts_attach' => 'nullable|array',
            'contacts_attach.*' => 'required|string|uuid|exists:contacts,id',
            'contacts_detach' => 'nullable|array',
            'contacts_detach.*' => 'required|string|uuid|exists:contacts,id',
        ];
    }

    public function messages()
    {
        return [
            'name.exists' => CPE_01,
            'vat.exists' => CPE_01
        ];
    }

    protected function nullValues()
    {
        return ['phone', 'website', 'vat', 'default_vendor_id', 'default_country_id', 'default_template_id'];
    }
}
