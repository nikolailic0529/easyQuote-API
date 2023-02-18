<?php

namespace Database\Factories;

use App\Domain\Contact\Enum\GenderEnum;
use App\Domain\Contact\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'contact_type' => $this->faker->randomElement(['Hardware', 'Software']),
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'contact_name' => $this->faker->name,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'mobile' => $this->faker->e164PhoneNumber,
            'phone' => $this->faker->e164PhoneNumber,
            'email' => $this->faker->companyEmail,
        ];
    }
}
