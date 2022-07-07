<?php

namespace App\Services\Task;

use App\Contracts\CauserAware;
use App\Contracts\LinkedToTasks;
use App\DTO\Tasks\CreateTaskData;
use App\DTO\Tasks\UpdateTaskData;
use App\Events\Task\TaskCreated;
use App\Events\Task\TaskDeleted;
use App\Events\Task\TaskUpdated;
use App\Models\{DateDay, DateMonth, DateWeek, RecurrenceType, Task\Task, Task\TaskRecurrence, Task\TaskReminder};
use App\Services\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;
use function tap;

class TaskEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected ValidatorInterface  $validator,
                                protected Dispatcher          $eventsDispatcher)
    {
    }

    public function createTaskForTaskable(CreateTaskData $data, Model&LinkedToTasks $linkedModel): Task
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new Task(), function (Task $task) use ($data, $linkedModel) {
            $task->{$task->getKeyName()} = (string)Uuid::generate(4);
            $task->user()->associate($this->causer);
            $task->activity_type = $data->activity_type;
            $task->name = $data->name;
            $task->content = $data->content;
            $task->expiry_date = Carbon::instance($data->expiry_date);
            $task->priority = $data->priority;

            $reminder = isset($data->reminder)
                ? tap(new TaskReminder(), function (TaskReminder $reminder) use ($data, $task): void {
                    $reminder->user()->associate($this->causer);
                    $reminder->task()->associate($task);
                    $reminder->set_date = Carbon::instance($data->reminder->set_date);
                    $reminder->status = $data->reminder->status;
                })
                : null;

            $recurrence = isset($data->recurrence)
                ? tap(new TaskRecurrence(), function (TaskRecurrence $recurrence) use ($data, $task): void {
                    $recurrence->user()->associate($this->causer);
                    $recurrence->task()->associate($task);
                    $recurrence->type()->associate(RecurrenceType::query()->where('value', $data->recurrence->type)->sole());
                    $recurrence->occur_every = $data->recurrence->occur_every;
                    $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                    $recurrence->start_date = Carbon::instance($data->recurrence->start_date);
                    $recurrence->end_date = isset($data->recurrence->end_date)
                        ? Carbon::instance($data->recurrence->end_date)
                        : null;
                    $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                    $recurrence->month()->associate(DateMonth::query()->where('value', $data->recurrence->month)->sole());
                    $recurrence->week()->associate(DateWeek::query()->where('value', $data->recurrence->week)->sole());
                    $recurrence->day_of_week = $data->recurrence->day_of_week;
                })
                : null;

            $this->connection->transaction(static function () use ($linkedModel, $task, $recurrence, $reminder, $data) {
                $task->save();

                $reminder?->save();
                $recurrence?->save();

                $task->users()->sync($data->users);
                $task->attachments()->sync($data->attachments);

                $linkedModel->tasks()->attach($task);
            });

            $this->eventsDispatcher->dispatch(new TaskCreated($task, $linkedModel));
        });
    }

    public function updateTask(Task $task, UpdateTaskData $data): Task
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($task, function (Task $task) use ($data) {
            $shouldDeleteNotifications = $task->expiry_date->ne($data->expiry_date);

            $task->activity_type = $data->activity_type;
            $task->name = $data->name;
            $task->content = $data->content;
            $task->expiry_date = Carbon::instance($data->expiry_date);
            $task->priority = $data->priority;

            $reminder = isset($data->reminder)
                ? tap($task->reminder ?? new TaskReminder(), function (TaskReminder $reminder) use ($data, $task): void {
                    if (false === $reminder->exists) {
                        $reminder->user()->associate($this->causer);
                    }
                    $reminder->task()->associate($task);
                    $reminder->set_date = Carbon::instance($data->reminder->set_date);
                    $reminder->status = $data->reminder->status;
                })
                : null;

            $recurrence = isset($data->recurrence)
                ? tap($task->recurrence ?? new TaskRecurrence(), function (TaskRecurrence $recurrence) use ($data, $task): void {
                    if (false === $recurrence->exists) {
                        $recurrence->user()->associate($this->causer);
                    }
                    $recurrence->task()->associate($task);
                    $recurrence->type()->associate(RecurrenceType::query()->where('value', $data->recurrence->type)->sole());
                    $recurrence->occur_every = $data->recurrence->occur_every;
                    $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                    $recurrence->start_date = Carbon::instance($data->recurrence->start_date);
                    $recurrence->end_date = isset($data->recurrence->end_date)
                        ? Carbon::instance($data->recurrence->end_date)
                        : null;
                    $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                    $recurrence->month()->associate(DateMonth::query()->where('value', $data->recurrence->month)->sole());
                    $recurrence->week()->associate(DateWeek::query()->where('value', $data->recurrence->week)->sole());
                    $recurrence->day_of_week = $data->recurrence->day_of_week;
                })
                : null;

            $task->load(['reminder', 'recurrence']);

            $usersSyncResult = [];

            $this->connection->transaction(static function () use ($data, $shouldDeleteNotifications, $task, $recurrence, $reminder, &$usersSyncResult) {
                $task->save();

                // Delete reminder from the task if null provided
                if (null === $reminder) {
                    $task->reminder?->delete();
                } else {
                    $reminder->save();
                }

                // Delete recurrence from the task if null provided
                if (null === $recurrence) {
                    $task->recurrence?->delete();
                } else {
                    $recurrence->save();
                }

                $usersSyncResult = $task->users()->sync($data->users);
                $task->attachments()->sync($data->attachments);

                if ($shouldDeleteNotifications) {
                    $task->notifications()->where('notification_key', 'expired')->delete();
                }
            });

            $this->eventsDispatcher->dispatch(new TaskUpdated($task, $usersSyncResult));
        });
    }

    public function deleteTask(Task $task): void
    {
        $this->connection->transaction(static fn() => $task->delete());

        $this->eventsDispatcher->dispatch(new TaskDeleted($task));
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}
