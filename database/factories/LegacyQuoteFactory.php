<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Contracts\Repositories\{CurrencyRepositoryInterface as Currencies,};
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Quote\Quote;
use App\Models\Template\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;

$factory->define(Quote::class, function (Faker $faker) {
    /** @var Company $company */
    $company = Company::factory()->create();

    $vendor = $company->vendors->whenEmpty(
        function ($vendors) use ($company) {
            $vendor = factory(Vendor::class)->create();

            $company->vendors()->syncWithoutDetaching([$vendor->getKey()]);

            return $vendor;
        },
        fn() => $company->vendors->random()
    );

    $country = $vendor->countries->random();

    $template = factory(QuoteTemplate::class)->create([
        'company_id' => $company->getKey(),
        'vendor_id' => $vendor->getKey(),
    ]);

    $template->countries()->sync($country->getKey());

    $sourceCurrency = app(Currencies::class)->all()->random();

    $targetCurrency = app(Currencies::class)->all()->random();

    $customer = factory(Customer::class)->create();

    $user = User::factory()->create();

    return [
        'user_id' => $user->id,
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'country_id' => $country->id,
        'customer_id' => $customer->id,
        'quote_template_id' => $template->id,
        'source_currency_id' => $sourceCurrency->id,
        'target_currency_id' => $targetCurrency->id,
        'exchange_rate_margin' => mt_rand(0, 99),
        'last_drafted_step' => Arr::random(array_keys(__('quote.stages'))),
        'pricing_document' => $faker->bankAccountNumber,
        'service_agreement_id' => $faker->bankAccountNumber,
        'system_handle' => $faker->bankAccountNumber,
        'additional_details' => $faker->sentences(10, true),
        'additional_notes' => $faker->sentences(10, true),
        'closing_date' => now()->addDays(rand(1, 10))->format('Y-m-d'),
        'calculate_list_price' => true,
        'buy_price' => (float)rand(10000, 40000),
        'custom_discount' => (float)rand(5, 99),
    ];
});

$factory->state(Quote::class, 'state', function () use ($factory) {
    $quote_data = Arr::except($factory->raw(Quote::class), 'customer_id');
    $margin = $factory->raw(CountryMargin::class, Arr::only($quote_data, ['country_id', 'vendor_id']));

    return compact('quote_data', 'margin');
});
