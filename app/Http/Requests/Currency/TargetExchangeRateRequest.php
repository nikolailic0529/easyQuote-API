<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Contracts\Repositories\CurrencyRepositoryInterface as Currencies;
use App\Models\Data\Currency;

class TargetExchangeRateRequest extends FormRequest
{
    protected $currencies;

    public function __construct(Currencies $currencies)
    {
        $this->currencies = $currencies;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'source_currency_id' => ['required', 'string', 'uuid', Rule::exists('currencies', 'id')],
            'target_currency_id' => ['required', 'string', 'uuid', Rule::exists('currencies', 'id')],
        ];
    }

    public function sourceCurrency(): Currency
    {
        return $this->currencies->find($this->source_currency_id);
    }

    public function targetCurrency(): Currency
    {
        return $this->currencies->find($this->target_currency_id);
    }
}
