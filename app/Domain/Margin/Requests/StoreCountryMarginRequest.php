<?php

namespace App\Domain\Margin\Requests;

use App\Domain\Margin\Enum\MarginMethodEnum;
use App\Domain\Margin\Enum\MarginQuoteTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreCountryMarginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_fixed' => [
                'required',
                'boolean',
            ],
            'vendor_id' => [
                'required',
                'uuid',
                Rule::exists('country_vendor')
                    ->where('country_id', $this->input('country_id')),
            ],
            'country_id' => [
                'required',
                'uuid',
                'exists:countries,id',
            ],
            'quote_type' => [
                'required',
                'string',
                new Enum(MarginQuoteTypeEnum::class),
            ],
            'method' => [
                'required',
                'string',
                new Enum(MarginMethodEnum::class),
            ],
            'value' => [
                'required',
                'numeric',
                Rule::when(!$this->boolean('is_fixed'), ['max:100']),
                Rule::unique('country_margins')
                    ->where('country_id', $this->input('country_id'))
                    ->where('vendor_id', $this->input('vendor_id'))
                    ->where('is_fixed', $this->boolean('is_fixed'))
                    ->where('method', $this->input('method'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('margin.validation.vendor_exists'),
            'value.unique' => __('margin.validation.value_unique'),
        ];
    }
}
