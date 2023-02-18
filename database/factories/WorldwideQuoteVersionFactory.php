<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Currency\Models\Currency;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
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
