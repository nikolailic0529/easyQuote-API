<?php

namespace App\Http\Requests\Company;

use App\DTO\Company\UpdateCompanyData;
use App\DTO\MissingValue;
use App\Enum\CompanyCategoryEnum;
use App\Enum\CompanySource;
use App\Enum\CompanyType;
use App\Enum\CustomerTypeEnum;
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\SalesUnit;
use App\Models\Vendor;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read Company $company
 */
class UpdateCompanyRequest extends FormRequest
{
    protected ?UpdateCompanyData $companyData = null;

    public function authorize(): Response
    {
        return (new CompanyRequestAuthResolver())($this);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sales_unit_id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],

            'name' => [
                'bail', 'required', 'string', 'max:191', 'min:2',
                Rule::unique(Company::class, 'name')
                    ->withoutTrashed()
                    ->ignore($this->company),
            ],
            'vat' => [
                'bail',
                Rule::when(static function (Fluent $data): bool {
                    return VAT::VAT_NUMBER === $data->get('vat_type');
                }, [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique(Company::class)
                        ->where('user_id', $this->company?->user_id)
                        ->withoutTrashed()
                        ->ignore($this->company),
                ], [
                    'nullable',
                    'exclude',
                ]),
            ],
            'vat_type' => [
                'required',
                'string',
                Rule::in(VAT::getValues()),
            ],
            'type' => [
                'bail',
                'required',
                'string',
                Rule::in(CompanyType::getValues()),
            ],
            'source' => [
                'bail',
                'nullable',
                function (string $attr, mixed $value, \Closure $fail): void {
                    if ($this->company->getFlag(Company::FROZEN_SOURCE) && $value !== $this->company->source) {
                        $fail("Forbidden to change source of the company.");
                    }
                },
                Rule::requiredIf(fn() => $this->input('type') === CompanyType::EXTERNAL),
                'string',
                Rule::in(CompanySource::getValues()),
            ],
            'short_code' => [
                Rule::requiredIf(fn() => $this->input('type') === CompanyType::INTERNAL),
                'string',
                'size:3',
                Rule::unique(Company::class)
                    ->where('user_id', $this->company?->user_id)
                    ->withoutTrashed()
                    ->ignore($this->company),
            ],
            'logo' => [
                'exclude_if:delete_logo,1',
                'image',
                'max:2048',
            ],
            'delete_logo' => 'boolean',
            'category' => [
                'nullable',
                'string',
                new Enum(CompanyCategoryEnum::class),
            ],
            'customer_type' => [
                'bail', 'nullable', 'string',
                new Enum(CustomerTypeEnum::class),
            ],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'min:4', 'phone'],
            'website' => ['nullable', 'string'],
            'vendors' => ['array'],
            'vendors.*' => ['required', 'uuid', Rule::exists(Vendor::class, 'id')->withoutTrashed()],
            'default_vendor_id' => [
                'nullable',
                'uuid',
                Rule::in($this->input('vendors', $this->company->vendors->modelKeys())),
            ],
            'default_country_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_vendor', 'country_id')
                    ->where('vendor_id', $this->input('default_vendor_id', fn() => $this->company->default_vendor_id)),
            ],
            'default_template_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_quote_template', 'quote_template_id')
                    ->where('country_id', $this->input('default_country_id', fn() => $this->company->default_country_id)),
            ],
            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Address::class, 'id')->withoutTrashed(),
            ],
            'addresses.*.is_default' => ['bail', 'required', 'boolean',],

            'contacts' => ['nullable', 'array'],
            'contacts.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Contact::class, 'id')->withoutTrashed(),
            ],
            'contacts.*.is_default' => ['bail', 'required', 'boolean',],
        ];
    }

    public function messages(): array
    {
        return [
            'name.exists' => CPE_01,
            'vat.exists' => CPE_01,
        ];
    }

    public function getUpdateCompanyData(): UpdateCompanyData
    {
        return $this->companyData ??= with(true, function (): UpdateCompanyData {
            $missing = new MissingValue();

            return new UpdateCompanyData([
                'sales_unit_id' => $this->input('sales_unit_id'),
                'name' => $this->input('name'),
                'vat' => $this->input('vat'),
                'vat_type' => $this->input('vat_type') ?? VAT::NO_VAT,
                'type' => $this->input('type'),
                'source' => $this->input('source') ?? $this->company->source,
                'short_code' => $this->input('short_code'),
                'logo' => $this->file('logo'),
                'delete_logo' => $this->boolean('delete_logo'),
                'category' => $this->input('category'),
                'customer_type' => $this->has('customer_type')
                    ? value(static function (?string $value): ?CustomerTypeEnum {
                        return isset($value) ? CustomerTypeEnum::from($value) : null;
                    }, $this->input('customer_type'))
                    : $missing,
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

    protected function prepareForValidation(): void
    {
        $nullableFields = $this->collect(['phone', 'website', 'vat', 'default_vendor_id', 'default_country_id',
            'default_template_id'])
            ->map(static fn(mixed $value): mixed => match ($value) {
                'null' => null,
                default => $value
            })
            ->all();

        $this->merge($nullableFields);

        $addresses = $this->collect('addresses')
            ->map(static fn(array $item): array => ['is_default' => filter_var($item['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                $item)
            ->all();

        $contacts = $this->collect('contacts')
            ->map(static fn(array $item): array => ['is_default' => filter_var($item['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                $item)
            ->all();

        $this->merge([
            'addresses' => $addresses,
            'contacts' => $contacts,
            'delete_logo' => $this->boolean('delete_logo'),
        ]);
    }
}
