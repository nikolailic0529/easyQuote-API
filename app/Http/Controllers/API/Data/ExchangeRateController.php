<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRate\ConvertCurrencies;
use App\Http\Resources\ExchangeRate\ConvertCurrencyResult;
use App\Services\ExchangeRate\CurrencyConverter;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    public function convertCurrencies(ConvertCurrencies $request, CurrencyConverter $currencyConverter): JsonResponse
    {
        $result = $currencyConverter->convertCurrencies(
            $request->getFromCurrencyCode(),
            $request->getToCurrencyCode(),
            $request->getAmount()
        );

        return response()->json(ConvertCurrencyResult::make([
            'from_currency_code' => $request->getFromCurrencyCode(),
            'to_currency_code' => $request->getToCurrencyCode(),
            'to_currency_symbol' => $request->getToCurrencySymbol(),
            'amount' => $request->getAmount(),
            'result' => $result,
        ]));
    }
}
