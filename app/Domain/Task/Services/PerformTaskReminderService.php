<?php

namespace App\Domain\Task\Services;

use App\Domain\AppEvent\Services\AppEventEntityService;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Task\Events\TaskReminderIsDue;
use App\Domain\Task\Models\TaskReminder;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PerformTaskReminderService implements LoggerAware
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
        $this->logger->info('Performing task reminders...');

        $reminders = \App\Domain\Task\Models\TaskReminder::query()
            ->where('status', '<>', ReminderStatus::Dismissed)
            ->has('task')
            ->lazy();

        foreach ($reminders as $reminder) {
            $this->processReminder($reminder);
        }
    }

    public function processReminder(TaskReminder $reminder): void
    {
        if (false === $this->isDue($reminder)) {
            return;
        }

        $this->logger->info('Processing task reminder.', [
            'reminder_id' => $reminder->getKey(),
            'task_id' => $reminder->task()->getParentKey(),
        ]);

        $this->eventDispatcher->dispatch(
            new TaskReminderIsDue($reminder)
        );

        $event = $this->eventEntityService->createAppEvent('task-reminder-performed', payload: [
            'reminder_id' => $reminder->getKey(),
        ]);

        $reminder->events()->attach($event);
    }

    public function isDue(TaskReminder $reminder): bool
    {
        $currentTime = Date::now();

        $setDate = Carbon::instance($reminder->set_date);

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
        return tap($this, fn () => $this->logger = $logger);
    }
}
