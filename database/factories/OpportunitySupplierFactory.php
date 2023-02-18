<?php

namespace Database\Factories;

use App\Domain\Worldwide\Models\OpportunitySupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunitySupplierFactory extends Factory
{
    protected $model = OpportunitySupplier::class;

    public function definition(): array
    {
        return [
            'supplier_name' => $this->faker->company(),
            'country_name' => $this->faker->country(),
            'contact_name' => $this->faker->firstName(),
            'contact_email' => $this->faker->companyEmail(),
        ];
    }
}
