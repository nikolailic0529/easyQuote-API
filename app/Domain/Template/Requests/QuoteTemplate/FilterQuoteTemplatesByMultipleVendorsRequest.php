<?php

namespace App\Domain\Template\Requests\QuoteTemplate;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterQuoteTemplatesByMultipleVendorsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')],
            'vendors' => ['present', 'array'],
            'vendors.*' => ['uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')],
            'country_id' => ['nullable', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
