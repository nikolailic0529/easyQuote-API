<?php

namespace App\Domain\Currency\Controllers\V1;

use App\Domain\Currency\Contracts\CurrencyRepositoryInterface as Currencies;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates as ExchangeRateService;
use App\Domain\ExchangeRate\Requests\TargetExchangeRateRequest;
use App\Foundation\Http\Controller;

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
     * Display all currencies having the exchange rate.
     *
     * @return \Illuminate\Http\Response
     */
    public function showAllHavingExrate()
    {
        return response()->json(
            $this->currencies->allHaveExrate()
        );
    }

    /**
     * Display target exchange rate based on source & target currencies.
     *
     * @return \Illuminate\Http\Response
     */
    public function targetRate(TargetExchangeRateRequest $request, ExchangeRateService $service)
    {
        $rate = $service->getTargetRate($request->sourceCurrency(), $request->targetCurrency(), 4);

        return response()->json(compact('rate'));
    }
}
