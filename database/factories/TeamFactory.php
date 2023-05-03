<?php

namespace Database\Factories;

use App\Domain\Team\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'team_name' => $this->faker->text(191),
            'monthly_goal_amount' => null,
        ];
    }
}
