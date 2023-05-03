<?php

namespace App\Domain\Template\Requests\HpeContractTemplate;

use App\Domain\Company\Models\Company;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHpeContractTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:2|max:80',
            'company_id' => ['required', 'uuid', Rule::exists(Company::class, 'id')->whereNull('deleted_at')],
            'vendor_id' => ['required', 'uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
            'countries' => 'required|array',
            'countries.*' => 'required|string|uuid|exists:countries,id',
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'form_data' => 'array',
        ];
    }
}
