<?php

namespace Tests\Unit;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\AppointmentReminder;
use App\Domain\Appointment\Services\PerformAppointmentReminderService;
use App\Domain\Reminder\Enum\ReminderStatus;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    public function testScheduledReminderIsDue(): void
    {
        /** @var \App\Domain\Appointment\Models\AppointmentReminder $reminder */
        $reminder = AppointmentReminder::factory()
            ->for(Appointment::factory([
                'start_date' => now(),
            ]))
            ->make([
                'start_date_offset' => 0,
                'status' => ReminderStatus::Scheduled,
            ]);

        $service = $this->app[PerformAppointmentReminderService::class];

        $this->assertTrue($service->isDue($reminder));

        $reminder->appointment->start_date = now()->addMinute();

        $this->assertFalse($service->isDue($reminder));

        $this->travelTo($reminder->appointment->start_date, function () use ($reminder, $service): void {
            $this->assertTrue($service->isDue($reminder));
        });
    }

    public function testScheduledReminderWithStartDateOffsetIsDue(): void
    {
        /** @var \App\Domain\Appointment\Models\AppointmentReminder $reminder */
        $reminder = AppointmentReminder::factory()
            ->for(Appointment::factory([
                'start_date' => now(),
            ]))
            ->make([
                'start_date_offset' => 600,
                'status' => ReminderStatus::Scheduled,
            ]);

        $service = $this->app[PerformAppointmentReminderService::class];

        $this->assertFalse($service->isDue($reminder));

        $this->travelTo($reminder->appointment->start_date->clone()->subSeconds(600),
            function () use ($reminder, $service): void {
                $this->assertTrue($service->isDue($reminder));
            });
    }

    public function testSnoozedReminderIsDue(): void
    {
        /** @var AppointmentReminder $reminder */
        $reminder = AppointmentReminder::factory()
            ->for(Appointment::factory([
                'start_date' => now(),
            ]))
            ->make([
                'start_date_offset' => 0,
                'snooze_date' => now()->addMinute(),
                'status' => ReminderStatus::Snoozed,
            ]);

        $service = $this->app[PerformAppointmentReminderService::class];

        $this->assertFalse($service->isDue($reminder));

        $this->travelTo($reminder->snooze_date, function () use ($reminder, $service): void {
            $this->assertTrue($service->isDue($reminder));
        });
    }
}
