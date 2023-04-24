<?php

namespace App\Domain\Country\Requests;

use App\Domain\Country\DataTransferObjects\UpdateCountryData;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Country::class)->ignore($this->route('country'))->withoutTrashed(),
            ],
            'iso_3166_2' => [
                'required',
                'string',
                'max:2',
                Rule::unique(Country::class, 'iso_3166_2')->ignore($this->route('country'))->withoutTrashed(),
            ],
            'default_currency_id' => [
                'nullable',
                'uuid',
                Rule::exists(Currency::class, (new Currency())->getKeyName()),
            ],
            'currency_code' => [
                'nullable',
                'string',
                'size:3',
            ],
            'currency_name' => [
                'nullable',
                'string',
                'max:50',
            ],
            'currency_symbol' => [
                'nullable',
                'string',
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'iso_3166_2.unique' => 'The given ISO Code has already taken.',
        ];
    }

    public function getUpdateCountryData(): UpdateCountryData
    {
        return UpdateCountryData::from($this->input());
    }
}
