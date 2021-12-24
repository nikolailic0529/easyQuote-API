<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use Faker\Generator as Faker;

$factory->define(Opportunity::class, function (Faker $faker) {
    $user = factory(\App\Models\User::class)->create();

    /** @var Company $primaryAccount */
    $primaryAccount = factory(Company::class)->create();
    /** @var Company $endCustomer */
    $endCustomer = factory(Company::class)->create();

    return [
        'pipeline_id' => PL_WWDP,
        'contract_type_id' => $faker->randomElement([CT_CONTRACT, CT_PACK]),
        'primary_account_id' => $primaryAccount->getKey(),
        'end_user_id' => $endCustomer->getKey(),
        'primary_account_contact_id' => \App\Models\Contact::value('id'),
        'account_manager_id' => $user->getKey(),

        'project_name' => $faker->text(40),

        'nature_of_service' => \Illuminate\Support\Str::random(40),
        'renewal_month' => $faker->randomElement([
            'TBC_Pack',
            '01_Jan',
            '02_Feb',
            '03_Mar',
            '04_Apr',
            '05_May',
            '06_Jun',
            '06_Jul',
            '08_Aug',
            '09_Sep',
            '10_Oct',
            '11_Nov',
            '12_Dec'
        ]),
        'renewal_year' => mt_rand(2020, 2100),
        'customer_status' => \Illuminate\Support\Str::random(40),
        'end_user_name' => \Illuminate\Support\Str::random(40),
        'hardware_status' => \Illuminate\Support\Str::random(40),
        'region_name' => \Illuminate\Support\Str::random(40),

        'opportunity_start_date' => $faker->dateTimeBetween('-60 days')->format('Y-m-d'),
        'opportunity_end_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'opportunity_closing_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'customer_order_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'purchase_order_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'supplier_order_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'supplier_order_transaction_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'supplier_order_confirmation_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        'expected_order_date' => $faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),

        'opportunity_amount' => (string)$faker->randomFloat(2, 1000, 10000),
        'base_opportunity_amount' => $faker->randomFloat(2, 1000, 10000),
        'opportunity_amount_currency_code' => $faker->currencyCode,
        'purchase_price' => (string)$faker->randomFloat(2, 1000, 10000),
        'base_purchase_price' => $faker->randomFloat(2, 1000, 10000),
        'purchase_price_currency_code' => $faker->currencyCode,
        'list_price' => (string)$faker->randomFloat(2, 1000, 10000),
        'base_list_price' => $faker->randomFloat(2, 1000, 10000),
        'list_price_currency_code' => $faker->currencyCode,
        'estimated_upsell_amount' => (string)$faker->randomFloat(2, 100, 1000),
        'estimated_upsell_amount_currency_code' => $faker->currencyCode,
        'margin_value' => (string)$faker->randomFloat(2, 10, 90),

        'account_manager_name' => $faker->name,
        'service_level_agreement_id' => \Illuminate\Support\Str::random(40),
        'sale_unit_name' => \Illuminate\Support\Str::random(40),
        'competition_name' => $faker->text(191),
        'drop_in' => $faker->text(191),
        'lead_source_name' => $faker->randomElement([
            '1 - Vendor Renewal',
            '2 - Conversion',
            '3 - Customer request',
            '4 - Vendor Lead',
            '5 - Employee'
        ]),

        'has_higher_sla' => $faker->boolean,
        'is_multi_year' => $faker->boolean,
        'has_additional_hardware' => $faker->boolean,
        'has_service_credits' => $faker->boolean,

        'remarks' => $faker->text(10000),
        'notes' => $faker->text(10000),
        'personal_rating' => $faker->randomElement([
            '1 - Need not confirmed',
            '2 - Need is confirmed',
            '3 - Good chance',
            '4 - Strong selling signals',
            '5 - Will be ordered'
        ]),
        'ranking' => $faker->randomFloat(2, 0, 1),

        'sale_action_name' => $faker->randomElement([
            "Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "PO Placed", "Processed in MC", "Closed",
        ]),
    ];
});
