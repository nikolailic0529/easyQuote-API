<?php

namespace Database\Factories;

use App\Domain\Appointment\Enum\InviteeResponse;
use App\Domain\Appointment\Enum\InviteeType;
use App\Domain\Appointment\Models\AppointmentContactInvitee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentContactInviteeFactory extends Factory
{
    protected $model = AppointmentContactInvitee::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'invitee_type' => InviteeType::Standard,
            'response' => InviteeResponse::NoResponse,
        ];
    }
}
