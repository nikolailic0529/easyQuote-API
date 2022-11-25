<?php

namespace Database\Factories;

use App\Enum\ReminderStatus;
use App\Models\Task\Task;
use App\Models\Task\TaskReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskReminderFactory extends Factory
{
    protected $model = TaskReminder::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'set_date' => now()->addDay(),
            'status' => ReminderStatus::Scheduled,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => ReminderStatus::Scheduled]);
    }

    public function snoozed(): static
    {
        return $this->state(['status' => ReminderStatus::Snoozed]);
    }

    public function dismissed(): static
    {
        return $this->state(['status' => ReminderStatus::Dismissed]);
    }

    public function forCurrentUser(): static
    {
        return $this->for(auth()->user());
    }
}

