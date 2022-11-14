<?php

namespace App\Http\Requests\Address;

use App\DTO\Address\CreateAddressData;
use App\Enum\AddressType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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

        return CreateAddressData::from($payload);
    }
}
