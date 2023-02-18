<?php

namespace Database\Factories;

use App\Domain\Address\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'address_type' => 'Equipment',
            'address_1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'post_code' => $this->faker->postcode(),
            'state' => $this->faker->state(),
            'state_code' => $this->faker->regexify('[A-Z]{2}'),
            'address_2' => $this->faker->streetAddress(),
            'country_id' => \App\Domain\Country\Models\Country::value('id'),
            'contact_name' => $this->faker->name(),
            'contact_number' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->email(),
        ];
    }
}
