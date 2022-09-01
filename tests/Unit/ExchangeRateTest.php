<?php

namespace Tests\Unit;

use App\Contracts\Services\ManagesExchangeRates as Service;
use App\Repositories\RateFileRepository as RateFiles;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use DatabaseTransactions;

    protected ?Service $service = null;

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

        $this->assertTrue(Carbon::parse($erUpdatedAfter)->gt($erUpdatedBefore));
    }

    /**
     * Test Exchange Rates update artisan command.
     *
     * @return void
     */
    public function testExchangeRatesUpdateCommand()
    {
        $this->artisan('eq:update-exchange-rates')->assertExitCode(1);

        $rateFiles = app(RateFiles::class);
        $files = $rateFiles->getAllNames();

        $this->artisan('eq:update-exchange-rates --file')
            ->expectsChoice('Which file?', head($files), $files)
            ->expectsOutput('Exchange Rates were successfully updated!')
            ->assertExitCode(1);
    }
}
