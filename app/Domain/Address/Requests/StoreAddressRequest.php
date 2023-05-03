<?php

namespace App\Domain\Address\Requests;

use App\Domain\Address\DataTransferObjects\CreateAddressData;
use App\Domain\Address\Enum\AddressType;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'address_type' => ['required', 'string', Rule::in(AddressType::getValues())],
            'address_1' => ['nullable', 'string', 'min:2', 'max:191'],
            'address_2' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'state_code' => ['nullable', 'string', 'max:191'],
            'post_code' => ['nullable', 'string', 'max:191'],
            'country_id' => [
                'required_without:country_code', 'string', 'uuid', Rule::exists(Country::class, 'id')->withoutTrashed(),
            ],
            'contact_id' => ['nullable', 'uuid', Rule::exists(Contact::class, 'id')->withoutTrashed()],
            'company_relations' => ['bail', 'nullable', 'array'],
            'company_relations.*.id' => [
                'bail', 'uuid', 'distinct', Rule::exists(Company::class, 'id')->withoutTrashed(),
            ],
            'company_relations.*.is_default' => [
                'bail', 'boolean',
            ],
        ];
    }

    protected function nullValues(): array
    {
        return ['address_2', 'city', 'state', 'post_code'];
    }

    public function getCreateAddressData(): CreateAddressData
    {
        $payload = $this->all();

        if ($this->isNotFilled('contact_id')) {
            unset($payload['contact_id']);
            $this->offsetUnset('contact_id');
        }

        return \App\Domain\Address\DataTransferObjects\CreateAddressData::from($payload);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareNullValues();
    }

    protected function prepareNullValues(): void
    {
        if (!method_exists($this, 'nullValues')) {
            return;
        }

        $nullValues = array_map(fn ($value) => ($value === 'null') ? null : $value, $this->only($this->nullValues()));

        $this->merge($nullValues);
    }
}
