<?php

namespace App\Domain\Company\Requests;

use App\Domain\Address\Models\Address;
use App\Domain\Company\DataTransferObjects\PartialUpdateCompanyData;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property Company $company
 */
class PartialUpdateCompanyRequest extends FormRequest
{
    protected ?PartialUpdateCompanyData $companyData = null;

    public function authorize(): Response
    {
        return (new CompanyRequestAuthResolver())($this);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sales_unit_id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
            'name' => [
                'required',
                'string',
                'max:191',
                'min:2',
                Rule::unique(Company::class, 'name')
                    ->withoutTrashed()
                    ->ignore($this->company),
            ],
            'logo' => [
                'exclude_if:delete_logo,1',
                'image',
                'max:2048',
            ],
            'delete_logo' => 'boolean',
            'email' => ['nullable', 'email'],
            'phone' => 'nullable|string|min:4|phone',
            'website' => 'nullable|string',
            'addresses' => ['array'],
            'addresses.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Address::class, 'id')->withoutTrashed(),
            ],
            'addresses.*.is_default' => [
                'bail', 'required', 'boolean',
            ],
            'contacts' => ['array'],
            'contacts.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Contact::class, 'id')->withoutTrashed(),
            ],
            'contacts.*.is_default' => [
                'bail', 'required', 'boolean',
            ],
        ];
    }

    public function getUpdateCompanyData(): PartialUpdateCompanyData
    {
        return $this->companyData ??= with(true, function (): PartialUpdateCompanyData {
            return new \App\Domain\Company\DataTransferObjects\PartialUpdateCompanyData([
                'sales_unit_id' => $this->input('sales_unit_id'),
                'name' => $this->input('name'),
                'logo' => $this->file('logo'),
                'delete_logo' => $this->boolean('delete_logo'),
                'email' => $this->input('email'),
                'phone' => $this->input('phone'),
                'website' => $this->input('website'),
                'addresses' => $this->input('addresses'),
                'contacts' => $this->input('contacts'),
            ]);
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('addresses')) {
            $addresses = $this->collect('addresses')
                ->map(function (array $addressData) {
                    return ['is_default' => filter_var($addressData['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                        $addressData;
                })
                ->all();

            $this->merge(['addresses' => $addresses]);
        }

        if ($this->has('contacts')) {
            $contacts = $this->collect('contacts')
                ->map(function (array $contactData) {
                    return ['is_default' => filter_var($contactData['is_default'] ?? false, FILTER_VALIDATE_BOOL)] +
                        $contactData;
                })
                ->all();

            $this->merge(['contacts' => $contacts]);
        }

        $this->merge(['delete_logo' => $this->boolean('delete_logo')]);
    }
}
