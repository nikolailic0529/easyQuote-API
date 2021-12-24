<?php

namespace App\Http\Requests\Address;

use App\DTO\Address\CreateAddressData;
use App\Models\Address;
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
    public function rules()
    {
        return [
            'address_type' => ['required', 'string', Rule::in(Address::TYPES)],
            'address_1' => 'nullable|string|min:2|max:191',
            'address_2' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'state_code' => 'nullable|string|max:191',
            'post_code' => 'nullable|string|max:191',
            'country_id' => 'required_without:country_code|string|uuid|exists:countries,id',
        ];
    }

    protected function nullValues()
    {
        return ['address_2', 'city', 'state', 'post_code'];
    }

    public function getCreateAddressData(): CreateAddressData
    {
        return new CreateAddressData([
            'address_type' => $this->input('address_type'),
            'address_1' => $this->input('address_1'),
            'address_2' => $this->input('address_2'),
            'city' => $this->input('city'),
            'state' => $this->input('state'),
            'state_code' => $this->input('state_code'),
            'post_code' => $this->input('post_code'),
            'country_id' => $this->input('country_id'),
        ]);
    }
}
