<?php

namespace Database\Factories;

use App\Models\Quote\WorldwideQuote;
use App\Models\SalesOrder;
use App\Models\Template\SalesOrderTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'worldwide_quote_id' => WorldwideQuote::factory()->state([
                'submitted_at' => now(),
                'contract_type_id' => CT_CONTRACT,
            ]),
            'order_number' => static function (array $attributes): string {
                return sprintf(
                    "EPD-WW-DP-CSO%'.07d",
                    WorldwideQuote::query()
                        ->whereKey($attributes['worldwide_quote_id'])
                        ->value('sequence_number')
                );
            },
            'sales_order_template_id' => SalesOrderTemplate::factory(),
            'vat_number' => Str::random(40),
            'customer_po' => Str::random(35),
        ];
    }
}

