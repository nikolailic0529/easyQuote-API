<?php

namespace Database\Factories;

use App\Domain\Priority\Enum\Priority;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Enum\TaskTypeEnum;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => $this->faker->randomElement(TaskTypeEnum::cases()),
            'user_id' => User::factory(),
            'name' => $this->faker->text(50),
            'content' => [
                [
                    'id' => $this->faker->uuid,
                    'name' => $this->faker->randomElement(['Single Column', 'Two Column', 'Three Column']),
                    'child' => [],
                    'class' => 'single-column field-dragger',
                    'order' => 1,
                    'controls' => [],
                    'is_field' => false,
                    'droppable' => false,
                    'decoration' => '1',
                ],
            ],
            'expiry_date' => now()->addYear()->format('Y-m-d H:i:s'),
            'priority' => $this->faker->randomElement(Priority::cases()),
        ];
    }

    public function expired(): TaskFactory
    {
        return $this->state(fn (): array => [
            'expiry_date' => now()->subDay(),
        ]);
    }
}
