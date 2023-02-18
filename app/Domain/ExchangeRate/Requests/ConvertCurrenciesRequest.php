<?php

namespace App\Domain\ExchangeRate\Requests;

use App\Domain\Currency\Models\Currency;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Currencies;

class ConvertCurrenciesRequest extends FormRequest
{
    const DEFAULT_TO_CURRENCY_CODE = 'GBP';

    protected ?string $fromCurrencyCodeCache = null;
    protected ?string $toCurrencyCodeCache = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $currencyCodes = Currencies::getCurrencyCodes();

        return [
            'from_currency_id' => [
                'bail', 'required_without:from_currency_code', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'from_currency_code' => [
                'bail', 'required_without:from_currency_id', 'string',
                Rule::in($currencyCodes),
            ],
            'to_currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'to_currency_code' => [
                'bail', 'nullable', 'string',
                Rule::in($currencyCodes),
            ],
            'amount' => [
                'bail', 'required', 'numeric',
            ],
            'exchange_date' => [
                'bail', 'nullable', 'date',
            ],
        ];
    }

    public function getFromCurrencyCode(): string
    {
        return $this->fromCurrencyCodeCache ??= value(function (): string {
            if ($this->has('from_currency_id')) {
                return Currency::query()
                    ->whereKey($this->input('from_currency_id'))
                    ->value('code');
            }

            return $this->input('from_currency_code');
        });
    }

    public function getToCurrencySymbol(): string
    {
        return Currencies::getSymbol($this->getToCurrencyCode());
    }

    public function getToCurrencyCode(): string
    {
        return $this->toCurrencyCodeCache ??= value(function (): string {
            $currencyCodeFromRequest = value(function (): ?string {
                if ($this->has('to_currency_id')) {
                    return Currency::query()
                        ->whereKey($this->input('to_currency_id'))
                        ->value('code');
                }

                return $this->input('to_currency_code');
            });

            return $currencyCodeFromRequest ?? self::DEFAULT_TO_CURRENCY_CODE;
        });
    }

    public function getExchangeDate(): ?\DateTimeInterface
    {
        return transform($this->input('exchange_date'), function (string $date): \DateTimeInterface {
            return Carbon::parse($date);
        });
    }

    public function getAmount(): float
    {
        return (float) $this->input('amount');
    }
}
