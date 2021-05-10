<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\Contract;
use App\Models\Template\ContractTemplate;
use Faker\Generator as Faker;

$factory->define(Contract::class, function (Faker $faker) {
    $quote = factory(\App\Models\Quote\Quote::class)->create();

    return [
        'quote_id' => $quote->getKey(),
//        'customer_id' => $quote->customer_id,
//        'country_id' => $quote->country_id,
//        'vendor_id' => $quote->vendor_id,
//        'company_id' => $quote->company_id,
//        'type' => $quote->type,
//        'pricing_document' => $quote->pricing_document,
//        'service_agreement_id' => $quote->service_agreement_id,
//        'system_handle' => $quote->system_handle,
//        'additional_details' => $quote->additional_details,
//        'closing_date' => $quote->closing_date,
//        'source_currency_id' => $quote->source_currency_id,
        'contract_template_id' => factory(ContractTemplate::class)->create()->getKey(),
    ];
});
