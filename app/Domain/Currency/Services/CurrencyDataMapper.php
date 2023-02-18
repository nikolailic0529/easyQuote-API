<?php

namespace App\Domain\Currency\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\ExchangeRate\Services\CurrencyConverter;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Models\CreateCurrencyExchangeRateInput;
use App\Domain\Pipeliner\Integration\Models\CreateCurrencyExchangeRateInputCollection;
use App\Domain\Pipeliner\Integration\Models\CreateCurrencyInput;
use App\Domain\Pipeliner\Integration\Models\CurrencyEntity;
use App\Domain\Pipeliner\Integration\Models\CurrencyExchangeRatesListEntity;
use Illuminate\Support\Collection;

class CurrencyDataMapper
{
    public function __construct(protected CurrencyConverter $currencyConverter)
    {
    }

    public function mapPipelinerCreateCurrencyInput(Currency $currency,
                                                    CurrencyEntity $baseCurrencyEntity,
                                                    array $exchangeRatesLists): CreateCurrencyInput
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
