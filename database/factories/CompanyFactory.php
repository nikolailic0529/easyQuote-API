<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'name' => $this->faker->company,
            'short_code' => $this->faker->unique()->regexify('/[A-Z]{3}/'),
            'vat' => $this->faker->unique()->bankAccountNumber(),
            'vat_type' => 'VAT Number',
            'type' => 'Internal',
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'website' => $this->faker->url,
        ];
    }
}
