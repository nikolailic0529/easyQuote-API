<?php

namespace Database\Factories;

use App\Enum\InviteeResponse;
use App\Enum\InviteeType;
use App\Models\Appointment\AppointmentContactInvitee;
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

