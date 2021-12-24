<?php

namespace App\Http\Requests\Company;

use App\DTO\Company\CreateCompanyData;
use App\Enum\CompanyCategory;
use App\Enum\CompanySource;
use App\Enum\CompanyType;
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    protected ?CreateCompanyData $companyData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:191',
                'min:2',
                Rule::unique(Company::class)
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
            'vat' => [
                'nullable',
                Rule::requiredIf($this->input('vat_type') === VAT::VAT_NUMBER),
                'string',
                'max:191',
                Rule::unique(Company::class)
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
            'vat_type' => [
                'nullable',
                'string',
                Rule::in(VAT::getValues()),
            ],
            'short_code' => [
                Rule::requiredIf(fn() => $this->type === CompanyType::INTERNAL),
                'string',
                'size:3',
                Rule::unique(Company::class)
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(CompanyType::getValues()),
            ],
            'source' => [
                'nullable',
                Rule::requiredIf(fn() => $this->type === CompanyType::EXTERNAL),
                'string',
                Rule::in(CompanySource::getValues()),
            ],
            'logo' => [
                'image',
                'max:2048',
            ],
            'category' => [
                'nullable',
                Rule::requiredIf(fn() => $this->type === CompanyType::EXTERNAL),
                'string',
                Rule::in(CompanyCategory::getValues()),
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
                Rule::in($this->vendors),
            ],
            'default_country_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_vendor', 'country_id')->where('vendor_id', $this->default_vendor_id),
            ],
            'default_template_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_quote_template', 'quote_template_id')->where('country_id', $this->default_country_id),
            ],

            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at'),
            ],
            'addresses.*.is_default' => [
                'bail', 'required', 'boolean',
            ],

            'contacts' => ['nullable', 'array'],
            'contacts.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Contact::class, 'id')->whereNull('deleted_at'),
            ],
            'contacts.*.is_default' => [
                'bail', 'required', 'boolean',
            ],
        ];
    }

    public function getCreateCompanyData(): CreateCompanyData
    {
        return $this->companyData ??= with(true, function (): CreateCompanyData {
            return new CreateCompanyData([
                'name' => $this->input('name'),
                'vat' => $this->input('vat'),
                'vat_type' => $this->input('vat_type') ?? VAT::NO_VAT,
                'type' => $this->input('type'),
                'source' => $this->input('source'),
                'short_code' => $this->input('short_code'),
                'logo' => $this->file('logo'),
                'category' => $this->input('category'),
                'email' => $this->input('email'),
                'phone' => $this->input('phone'),
                'website' => $this->input('website'),
                'vendors' => $this->input('vendors') ?? [],
                'default_vendor_id' => $this->input('default_vendor_id'),
                'default_template_id' => $this->input('default_template_id'),
                'default_country_id' => $this->input('default_country_id'),
                'addresses' => $this->input('addresses') ?? [],
                'contacts' => $this->input('contacts') ?? [],
            ]);
        });
    }

    public function messages()
    {
        return [
            'name.exists' => CPE_01,
            'vat.exists' => CPE_01,
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = array_map(function ($value) {
            if ($value === 'null') {
                return null;
            }

            return $value;
        }, $this->only(['phone', 'website', 'vat', 'default_vendor_id', 'default_country_id', 'default_template_id']));

        $this->merge($nullableFields);

        $addresses = array_map(
            function (array $addressData) {
                return ['is_default' => filter_var($addressData['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                    $addressData;
            },
            $this->input('addresses') ?? []
        );

        $contacts = array_map(
            function (array $contactData) {
                return ['is_default' => filter_var($contactData['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                    $contactData;
            },
            $this->input('contacts') ?? []
        );

        $this->merge([
            'addresses' => $addresses,
            'contacts' => $contacts,
        ]);
    }
}
