<?php

namespace Tests\Feature;

use App\Models\Data\Currency;
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

    /**
     * Test an ability to convert exchange rates with specified date.
     *
     * @return void
     */
    public function testCanConvertExchangeRatesWithSpecifiedDate()
    {
        $this->postJson('api/exchange-rates/convert', [
            'from_currency_code' => 'EUR',
            'to_currency_code' => 'GBP',
            'amount' => 1000.00,
            'exchange_date' => today(),
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
            'amount' => 1000.00,
            'exchange_date' => today(),
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

    /**
     * Test an ability to convert exchange rates with currency model keys.
     *
     * @return void
     */
    public function testCanConvertExchangeRatesWithModelKeys()
    {
        $response = $this->postJson('api/exchange-rates/convert', [
            'from_currency_id' => Currency::query()->where('code', 'EUR')->value('id'),
            'to_currency_id' => Currency::query()->where('code', 'GBP')->value('id'),
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

        $responseWithCodes = $this->postJson('api/exchange-rates/convert', [
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

        $response->assertJson($responseWithCodes->json());

        $response = $this->postJson('api/exchange-rates/convert', [
            'from_currency_id' => Currency::query()->where('code', 'USD')->value('id'),
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

        $responseWithCodes = $this->postJson('api/exchange-rates/convert', [
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

        $response->assertJson($responseWithCodes->json());
    }
}
