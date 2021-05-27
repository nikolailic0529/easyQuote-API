<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\SalesOrder;
use Faker\Generator as Faker;

$factory->define(SalesOrder::class, function (Faker $faker) {
    $quote = factory(\App\Models\Quote\WorldwideQuote::class)->create([
        'contract_type_id' => CT_CONTRACT,
        'submitted_at' => now()
    ]);
    $salesOrderTemplate = factory(\App\Models\Template\SalesOrderTemplate::class)->create([
        'business_division_id' => BD_WORLDWIDE,
        'contract_type_id' => CT_CONTRACT
    ]);

    return [
        'worldwide_quote_id' => $quote->getKey(),
        'order_number' => sprintf("EPD-WW-DP-CSO%'.07d", $quote->sequence_number),
        'sales_order_template_id' => $salesOrderTemplate->getKey(),
        'vat_number' => \Illuminate\Support\Str::random(40),
        'customer_po' => \Illuminate\Support\Str::random(35)
    ];
});
