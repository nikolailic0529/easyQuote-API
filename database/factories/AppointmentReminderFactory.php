<?php

namespace Database\Factories;

use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentReminderFactory extends Factory
{
    protected $model = AppointmentReminder::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'start_date_offset' => $this->faker->randomElement([
                5 * 60, // 5 minutes before
                10 * 60, // 10 minutes before
                15 * 60, // 15 minutes before
                30 * 60, // 30 minutes before
                60 * 60, // ...
                2 * 60 * 60,
                4 * 60 * 60,
                8 * 60 * 60,
                12 * 60 * 60,
                24 * 60 * 60,
                48 * 60 * 60,
                7 * 24 * 60 * 60, // a week before
                14 * 24 * 60 * 60, // 2 weeks before
            ]),
            'snooze_date' => null,
            'status' => \App\Enum\ReminderStatus::Scheduled,
        ];
    }
}

