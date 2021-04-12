<?php

namespace App\Http\Requests\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Currencies;

class ConvertCurrencies extends FormRequest
{
    const DEFAULT_TO_CURRENCY_CODE = 'GBP';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $currencyCodes = Currencies::getCurrencyCodes();

        return [
            'from_currency_code' => [
                'bail', 'required', 'string',
                Rule::in($currencyCodes)
            ],
            'to_currency_code' => [
                'bail', 'nullable', 'string',
                Rule::in($currencyCodes)
            ],
            'amount' => [
                'bail', 'required', 'numeric'
            ]
        ];
    }

    public function getFromCurrencyCode(): string
    {
        return $this->input('from_currency_code');
    }

    public function getToCurrencySymbol(): string
    {
        return Currencies::getSymbol($this->getToCurrencyCode());
    }

    public function getToCurrencyCode(): string
    {
        return $this->input('to_currency_code') ?? self::DEFAULT_TO_CURRENCY_CODE;
    }

    public function getAmount(): float
    {
        return (float)$this->input('amount');
    }
}
