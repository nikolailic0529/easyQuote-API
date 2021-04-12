<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    /**
     * Test an ability to convert exchange rates.
     *
     * @return void
     */
    public function testCanConvertExchangeRates()
    {
        $this->postJson('api/exchange-rates/convert', [
            'from_currency_code' => 'EUR',
            'to_currency_code' => 'GBP',
            'amount' => 1000.00
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'from_currency_code',
                'to_currency_code',
                'amount',
                'result',
                'result_formatted'
            ]);

        $this->postJson('api/exchange-rates/convert', [
            'from_currency_code' => 'USD',
//            'to_currency_code' => 'GBP',
            'amount' => 1000.00
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'from_currency_code',
                'to_currency_code',
                'amount',
                'result',
                'result_formatted'
            ]);
    }
}
