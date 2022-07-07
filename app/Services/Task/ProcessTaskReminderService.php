<?php

namespace App\Services\Task;

use App\Contracts\LoggerAware;
use App\Contracts\Services\NotificationFactory;
use App\Models\Task\TaskReminder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProcessTaskReminderService implements LoggerAware
{
    public function __construct(protected ConnectionInterface $connection,
                                protected NotificationFactory $notificationFactory,
                                protected LoggerInterface     $logger = new NullLogger())
    {
    }

    public function process(): void
    {
        $this->logger->info('Performing task reminders...');

        foreach (TaskReminder::query()->cursor() as $reminder) {
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

        $this->notificationFactory
            ->for($reminder->user ?? $reminder->task->user)
            ->priority($reminder->task->priority)
            ->subject($reminder->task)
            ->message("[Reminder]: {$reminder->task->name}")
            ->push();
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
        return tap($this, fn() => $this->logger = $logger);
    }
}