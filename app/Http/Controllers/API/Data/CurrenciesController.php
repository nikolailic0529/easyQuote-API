<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\CurrencyRepositoryInterface;

class CurrenciesController extends Controller
{
    protected $currency;

    public function __construct(CurrencyRepositoryInterface $currency)
    {
        $this->currency = $currency;
    }

    public function __invoke()
    {
        $currencies = $this->currency->all();
        return response()->json($currencies);
    }
}
