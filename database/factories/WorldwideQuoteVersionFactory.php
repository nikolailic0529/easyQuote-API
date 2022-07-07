<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorldwideQuoteVersionFactory extends Factory
{
    protected $model = WorldwideQuoteVersion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::query()->where('short_code', $this->faker->randomElement(['SWH', 'EPD', 'THG']))->value('id'),
            'quote_currency_id' => Currency::query()->where('code', $this->faker->randomElement(['GBP', 'USD']))->value('id'),
        ];
    }
}

