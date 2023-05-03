<?php

namespace Database\Factories;

use App\Domain\Appointment\Enum\AppointmentTypeEnum;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\SalesUnit\Models\SalesUnit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'subject' => $this->faker->text(100),
            'description' => $this->faker->realText(),
            'location' => $this->faker->city(),
            'start_date' => $startDate = $this->faker->dateTimeBetween('+1d', '+3days'),
            'end_date' => $this->faker->dateTimeBetween(Carbon::instance($startDate)->addDay(), '+10days'),
        ];
    }
}
