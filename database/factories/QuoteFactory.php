<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'vendor_id' => \factory(Vendor::class)->create()->getKey(),
            'country_id' => Country::query()->get()->random()->getKey(),
            'customer_id' => \factory(Customer::class)->create()->getKey(),
            'quote_template_id' => \factory(QuoteTemplate::class)->create()->getKey(),
            'source_currency_id' => Currency::query()->get()->random()->getKey(),
            'target_currency_id' => Currency::query()->get()->random()->getKey(),
            'exchange_rate_margin' => mt_rand(0, 99),
            'last_drafted_step' => Arr::random(array_keys(__('quote.stages'))),
            'pricing_document' => $this->faker->bankAccountNumber,
            'service_agreement_id' => $this->faker->bankAccountNumber,
            'system_handle' => $this->faker->bankAccountNumber,
            'additional_details' => $this->faker->sentences(10, true),
            'additional_notes' => $this->faker->sentences(10, true),
            'closing_date' => now()->addDays(rand(1, 10))->format('Y-m-d'),
            'calculate_list_price' => true,
            'buy_price' => (float) rand(10000, 40000),
            'custom_discount' => (float) rand(5, 99),
        ];
    }
}
