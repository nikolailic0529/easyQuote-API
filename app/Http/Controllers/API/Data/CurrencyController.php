<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\CurrencyRepositoryInterface as Currencies;
use App\Contracts\Services\ExchangeRateServiceInterface as ExchangeRateService;
use App\Http\Requests\Currency\TargetExchangeRateRequest;

class CurrencyController extends Controller
{
    protected $currencies;

    public function __construct(Currencies $currencies)
    {
        $this->currencies = $currencies;

    }

    public function __invoke()
    {
        return response()->json($this->currencies->all());
    }

    /**
     * Display target exchange rate based on source & target currencies.
     *
     * @param \App\Http\Requests\Currency\TargetExchangeRateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function targetRate(TargetExchangeRateRequest $request, ExchangeRateService $service)
    {
        $rate = $service->getTargetRate($request->sourceCurrency(), $request->targetCurrency(), 4);

        return response()->json(compact('rate'));
    }
}
