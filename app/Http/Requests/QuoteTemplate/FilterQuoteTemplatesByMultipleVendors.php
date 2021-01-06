<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterQuoteTemplatesByMultipleVendors extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id'        => ['required', 'uuid', Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')],
            'vendors'           => ['present'],
            'vendors.*'         => ['uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')],
            'country_id'        => ['nullable', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
