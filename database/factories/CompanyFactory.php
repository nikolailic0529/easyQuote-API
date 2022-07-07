<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
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

