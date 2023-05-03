<?php

namespace App\Domain\Margin\Requests;

use App\Domain\Margin\Enum\MarginMethodEnum;
use App\Domain\Margin\Enum\MarginQuoteTypeEnum;
use App\Domain\Margin\Models\CountryMargin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateCountryMarginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_fixed' => [
                'boolean',
            ],
            'vendor_id' => [
                'uuid',
                Rule::exists('country_vendor')
                    ->where('country_id', $this->input('country_id')),
            ],
            'country_id' => [
                'uuid',
                'exists:countries,id',
            ],
            'quote_type' => [
                'string',
                new Enum(MarginQuoteTypeEnum::class),
            ],
            'method' => [
                'string',
                new Enum(MarginMethodEnum::class),
            ],
            'value' => [
                'numeric',
                Rule::when(!$this->boolean('is_fixed'), ['max:100']),
                Rule::unique('country_margins')
                    ->where('country_id', $this->input('country_id', fn (): ?string => $this->getMarginModel()->country()->getParentKey()))
                    ->where('vendor_id', $this->input('vendor_id', fn (): ?string => $this->getMarginModel()->vendor()->getParentKey()))
                    ->where('is_fixed', $this->input('is_fixed', fn (): ?bool => $this->getMarginModel()->is_fixed))
                    ->where('method', $this->input('method', fn (): ?string => $this->getMarginModel()->method))
                    ->withoutTrashed()
                    ->ignore($this->getMarginModel()),
            ],
        ];
    }

    public function getMarginModel(): CountryMargin
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('margin');
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('margin.validation.vendor_exists'),
            'value.unique' => __('margin.validation.value_unique'),
        ];
    }
}
