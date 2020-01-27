<?php

namespace Tests\Unit;

use Tests\TestCase;
use \App\Contracts\Services\ExchangeRateServiceInterface as Service;

class ExchangeRateTest extends TestCase
{
    /** @var \App\Contracts\Services\ExchangeRateServiceInterface */
    protected $service;

    protected function setUp(): void
    {
        parent::{__FUNCTION__}();

        $this->service = app(Service::class);
    }

    /**
     * Test Exchange Rates updating with valid period.
     * Exchange Rates Update Setting value should be new after updating the Rates.
     *
     * @return void
     */
    public function testExchangeRatesUpdatingUsingValidPeriod()
    {
        $erUpdatedBefore = setting(ER_SETTING_UPDATE_KEY);

        $result = $this->service->updateRates();

        $erUpdatedAfter = setting(ER_SETTING_UPDATE_KEY);

        $this->assertTrue($result);

        $this->assertTrue($erUpdatedAfter->gt($erUpdatedBefore));
    }
}
