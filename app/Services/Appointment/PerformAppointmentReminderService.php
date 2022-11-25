<?php

namespace App\Services\Appointment;

use App\Contracts\LoggerAware;
use App\Enum\ReminderStatus;
use App\Events\Appointment\AppointmentReminderIsDue;
use App\Models\Appointment\AppointmentReminder;
use App\Services\AppEvent\AppEventEntityService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PerformAppointmentReminderService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly AppEventEntityService $eventEntityService,
        protected LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function process(): void
    {
        $this->logger->info('Performing appointment reminders...');

        $reminders = AppointmentReminder::query()
            ->where('status', '<>', ReminderStatus::Dismissed)
            ->has('appointment')
            ->lazy();

        foreach ($reminders as $reminder) {
            $this->processReminder($reminder);
        }
    }

    public function processReminder(AppointmentReminder $reminder): void
    {
        if (false === $this->isDue($reminder)) {
            return;
        }

        $this->logger->info('Appointment reminder is due.', [
            'reminder_id' => $reminder->getKey(),
            'appointment_id' => $reminder->appointment()->getParentKey(),
        ]);

        $this->eventDispatcher->dispatch(
            new AppointmentReminderIsDue($reminder)
        );

        $event = $this->eventEntityService->createAppEvent('appointment-reminder-performed', payload: [
            'reminder_id' => $reminder->getKey(),
        ]);

        $reminder->events()->attach($event);
    }

    public function isDue(AppointmentReminder $reminder): bool
    {
        $currentTime = Date::now();

        if (ReminderStatus::Snoozed === $reminder->status) {
            if (null === $reminder->snooze_date) {
                $this->logger->warning("Appointment reminder was snoozed, but snooze date is missing.", [
                    'reminder_id' => $reminder->getKey(),
                ]);

                return false;
            }

            $setDate = Carbon::instance($reminder->snooze_date);
        } else {
            $setDate = static::dropSeconds(Carbon::instance($reminder->appointment->start_date))
                ->subSeconds((int) $reminder->start_date_offset);
        }

        return static::dropSeconds($currentTime)->getTimestamp() === static::dropSeconds($setDate)->getTimestamp();
    }

    protected static function dropSeconds(\DateTimeInterface $dateTime): \DateTimeInterface
    {
        return $dateTime->setTime(
            $dateTime->format('H'),
            $dateTime->format('i'),
            0
        );
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}