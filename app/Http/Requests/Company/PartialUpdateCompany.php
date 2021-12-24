<?php

namespace App\Http\Requests\Company;

use App\DTO\Company\PartialUpdateCompanyData;
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\QuoteFile\MappedRow;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read Company $company
 */
class PartialUpdateCompany extends FormRequest
{
    protected ?PartialUpdateCompanyData $companyData = null;

    public function authorize(): Response
    {
        return (new CompanyRequestAuthResolver())($this);
    }

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
            'email' => 'email',
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
            return new PartialUpdateCompanyData([
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
