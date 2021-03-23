<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\SalesOrder;
use App\Services\SalesOrder\SalesOrderNumberHelper;
use Faker\Generator as Faker;

$factory->define(SalesOrder::class, function (Faker $faker) {
    $quote = factory(\App\Models\Quote\WorldwideQuote::class)->create([
        'contract_type_id' => CT_CONTRACT,
        'submitted_at' => now()
    ]);
    $contractTemplate = factory(\App\Models\Template\ContractTemplate::class)->create([
        'business_division_id' => BD_WORLDWIDE,
        'contract_type_id' => CT_CONTRACT
    ]);

    return [
        'worldwide_quote_id' => $quote->getKey(),
        'order_number' => SalesOrderNumberHelper::makeSalesOrderNumber('Contract', $quote->sequence_number),
        'contract_template_id' => $contractTemplate->getKey(),
        'vat_number' => \Illuminate\Support\Str::random(40),
        'customer_po' => \Illuminate\Support\Str::random(35)
    ];
});
