<?php

namespace App\Domain\ExchangeRate\Providers;

use App\Domain\ExchangeRate\Events\ExchangeRatesUpdated;
use App\Domain\ExchangeRate\Listeners\ExchangeRatesListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class ExchangeRateEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        ExchangeRatesUpdated::class => [
            ExchangeRatesListener::class,
        ],
    ];
}
