<?php

namespace App\Domain\ExchangeRate\Controllers\V1;

use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\ExchangeRate\Requests\ConvertCurrenciesRequest;
use App\Domain\ExchangeRate\Resources\V1\ConvertCurrencyResult;
use App\Domain\ExchangeRate\Services\CurrencyConverter;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExchangeRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function convertCurrencies(ConvertCurrenciesRequest $request, CurrencyConverter $currencyConverter): JsonResponse
    {
        $result = $currencyConverter->convertCurrencies(
            $request->getFromCurrencyCode(),
            $request->getToCurrencyCode(),
            $request->getAmount(),
            $request->getExchangeDate()
        );

        return response()->json(ConvertCurrencyResult::make([
            'from_currency_code' => $request->getFromCurrencyCode(),
            'to_currency_code' => $request->getToCurrencyCode(),
            'to_currency_symbol' => $request->getToCurrencySymbol(),
            'amount' => $request->getAmount(),
            'exchange_date' => $request->getExchangeDate(),
            'result' => $result,
        ]));
    }

    /**
     * Refresh exchange rates.
     * Loads the newest rates from the data provider.
     *
     * @throws AuthorizationException
     */
    public function refreshExchangeRates(Request $request, ManagesExchangeRates $service): Response
    {
        $this->authorize('refresh', ExchangeRate::class);

        $service->updateRates();

        return response()->noContent();
    }
}
