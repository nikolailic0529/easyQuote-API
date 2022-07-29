<?php

namespace App\Services\Currency;

use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\CreateCurrencyExchangeRateInput;
use App\Integrations\Pipeliner\Models\CreateCurrencyExchangeRateInputCollection;
use App\Integrations\Pipeliner\Models\CreateCurrencyInput;
use App\Integrations\Pipeliner\Models\CurrencyEntity;
use App\Integrations\Pipeliner\Models\CurrencyExchangeRatesListEntity;
use App\Models\Data\Currency;
use App\Services\ExchangeRate\CurrencyConverter;
use Illuminate\Support\Collection;

class CurrencyDataMapper
{
    public function __construct(protected CurrencyConverter $currencyConverter)
    {
    }

    public function mapPipelinerCreateCurrencyInput(Currency       $currency,
                                                    CurrencyEntity $baseCurrencyEntity,
                                                    array          $exchangeRatesLists): CreateCurrencyInput
    {
        $exchangeRates = collect($exchangeRatesLists)
            ->map(function (CurrencyExchangeRatesListEntity $entity) use ($currency, $baseCurrencyEntity): CreateCurrencyExchangeRateInput {
                $rate = $this->currencyConverter->convertCurrencies(
                    fromCode: $baseCurrencyEntity->code,
                    toCode: $currency->code,
                    amount: 1,
                    dateTime: $entity->validFrom
                );

                return new CreateCurrencyExchangeRateInput(
                    currencyExchangeRateListId: $entity->id,
                    exchangeRate: $rate,
                );
            })
            ->pipe(static function (Collection $collection): CreateCurrencyExchangeRateInputCollection {
                return new CreateCurrencyExchangeRateInputCollection(...$collection->all());
            });

        return new CreateCurrencyInput(
            code: $currency->code,
            currencyExchangeRates: $exchangeRates,
            symbol: $currency->symbol ?? InputValueEnum::Miss,
        );
    }
}