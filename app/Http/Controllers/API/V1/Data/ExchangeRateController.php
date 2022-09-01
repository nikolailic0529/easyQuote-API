<?php

namespace App\Http\Controllers\API\V1\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRate\ConvertCurrencies;
use App\Models\Data\ExchangeRate;
use App\Http\Resources\V1\ExchangeRate\ConvertCurrencyResult;
use App\Services\ExchangeRate\CurrencyConverter;
use App\Contracts\Services\ManagesExchangeRates;
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

    public function convertCurrencies(ConvertCurrencies $request, CurrencyConverter $currencyConverter): JsonResponse
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
