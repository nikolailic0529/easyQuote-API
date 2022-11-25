<?php

namespace Tests\Unit;

use App\Enum\ReminderStatus;
use App\Models\Task\TaskReminder;
use App\Services\Task\PerformTaskReminderService;
use Tests\TestCase;

/**
 * @group build
 */
class TaskReminderTest extends TestCase
{
    public function testScheduledReminderIsDue(): void
    {
        /** @var TaskReminder $reminder */
        $reminder = TaskReminder::factory()->make([
            'set_date' => now(),
            'status' => ReminderStatus::Scheduled,
        ]);

        $service = $this->app[PerformTaskReminderService::class];

        $this->assertTrue($service->isDue($reminder));

        $reminder->set_date = now()->addMinute();

        $this->assertFalse($service->isDue($reminder));

        $this->travelTo($reminder->set_date, function () use ($reminder, $service): void {
            $this->assertTrue($service->isDue($reminder));
        });
    }

    public function testSnoozedReminderIsDue(): void
    {
        /** @var TaskReminder $reminder */
        $reminder = TaskReminder::factory()->make([
            'set_date' => now()->addMinute(),
            'status' => ReminderStatus::Snoozed,
        ]);

        $service = $this->app[PerformTaskReminderService::class];

        $this->assertFalse($service->isDue($reminder));

        $this->travelTo($reminder->set_date, function () use ($reminder, $service): void {
            $this->assertTrue($service->isDue($reminder));
        });
    }
}
