<?php

namespace App\Domain\Task\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Date\Models\DateDay;
use App\Domain\Date\Models\DateMonth;
use App\Domain\Date\Models\DateWeek;
use App\Domain\Recurrence\Models\{RecurrenceType
};
use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\DataTransferObjects\CreateTaskData;
use App\Domain\Task\DataTransferObjects\SetTaskReminderData;
use App\Domain\Task\DataTransferObjects\UpdateTaskData;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskUpdated;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskRecurrence;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\User\Models\User;
use App\Foundation\Validation\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class TaskEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected ValidatorInterface $validator,
        protected Dispatcher $eventsDispatcher
    ) {
    }

    public function createTaskForTaskable(CreateTaskData $data, Model&LinkedToTasks $linkedModel): Task
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap(new Task(), function (Task $task) use ($data, $linkedModel) {
            $task->{$task->getKeyName()} = (string) Uuid::generate(4);
            $task->user()->associate($this->causer);
            $task->salesUnit()->associate($data->sales_unit_id);
            $task->activity_type = $data->activity_type;
            $task->name = $data->name;
            $task->content = $data->content;
            $task->expiry_date = isset($data->expiry_date) ? Carbon::instance($data->expiry_date) : null;
            $task->priority = $data->priority;

            $reminder = isset($data->reminder)
                ? \tap(new \App\Domain\Task\Models\TaskReminder(), function (TaskReminder $reminder) use ($data, $task): void {
                    $reminder->user()->associate($this->causer);
                    $reminder->task()->associate($task);
                    $reminder->forceFill($data->reminder->all());
                })
                : null;

            $recurrence = isset($data->recurrence)
                ? \tap(new TaskRecurrence(), function (TaskRecurrence $recurrence) use ($data, $task): void {
                    $recurrence->user()->associate($this->causer);
                    $recurrence->task()->associate($task);
                    $recurrence->type()->associate(RecurrenceType::query()
                        ->where('value', $data->recurrence->type)
                        ->sole());
                    $recurrence->occur_every = $data->recurrence->occur_every;
                    $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                    $recurrence->start_date = Carbon::instance($data->recurrence->start_date);
                    $recurrence->end_date = isset($data->recurrence->end_date)
                        ? Carbon::instance($data->recurrence->end_date)
                        : null;
                    $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                    $recurrence->month()->associate(DateMonth::query()
                        ->where('value', $data->recurrence->month)
                        ->sole());
                    $recurrence->week()->associate(DateWeek::query()->where('value', $data->recurrence->week)->sole());
                    $recurrence->day_of_week = $data->recurrence->day_of_week;
                })
                : null;

            $this->connection->transaction(function () use ($linkedModel, $task, $recurrence, $reminder, $data): void {
                $task->save();

                $reminder?->save();
                $recurrence?->save();

                $task->users()->sync($data->users);
                $task->attachments()->sync($data->attachments);

                $linkedModel->tasks()->attach($task);

                $this->touchRelated($task);
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

        return \tap($task, function (Task $task) use ($data) {
            $shouldDeleteNotifications = is_null($task->expiry_date) || $task->expiry_date->ne($data->expiry_date);

            $task->salesUnit()->associate($data->sales_unit_id);
            $task->activity_type = $data->activity_type;
            $task->name = $data->name;
            $task->content = $data->content;
            $task->expiry_date = isset($data->expiry_date) ? Carbon::instance($data->expiry_date) : null;
            $task->priority = $data->priority;

            $existingReminder = $this->causer instanceof User
                ? $task->activeReminders()->whereBelongsTo($this->causer)->first()
                : null;

            $reminder = isset($data->reminder)
                ? \tap($existingReminder ?? new TaskReminder(),
                    function (TaskReminder $reminder) use ($data, $task): void {
                        if (false === $reminder->exists) {
                            $reminder->user()->associate($this->causer);
                        }
                        $reminder->task()->associate($task);
                        $reminder->forceFill($data->reminder->all());
                    })
                : null;

            $recurrence = isset($data->recurrence)
                ? \tap($task->recurrence ?? new TaskRecurrence(),
                    function (TaskRecurrence $recurrence) use ($data, $task): void {
                        if (false === $recurrence->exists) {
                            $recurrence->user()->associate($this->causer);
                        }
                        $recurrence->task()->associate($task);
                        $recurrence->type()->associate(RecurrenceType::query()
                            ->where('value', $data->recurrence->type)
                            ->sole());
                        $recurrence->occur_every = $data->recurrence->occur_every;
                        $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                        $recurrence->start_date = Carbon::instance($data->recurrence->start_date);
                        $recurrence->end_date = isset($data->recurrence->end_date)
                            ? Carbon::instance($data->recurrence->end_date)
                            : null;
                        $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                        $recurrence->month()->associate(DateMonth::query()
                            ->where('value', $data->recurrence->month)
                            ->sole());
                        $recurrence->week()->associate(DateWeek::query()
                            ->where('value', $data->recurrence->week)
                            ->sole());
                        $recurrence->day_of_week = $data->recurrence->day_of_week;
                    })
                : null;

            $task->load(['reminder', 'recurrence']);

            $usersSyncResult = [];

            $this->connection->transaction(function () use (
                $data,
                $shouldDeleteNotifications,
                $task,
                $recurrence,
                $reminder,
                &$usersSyncResult
            ): void {
                $task->save();

                $reminder?->save();

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

                $this->touchRelated($task);
            });

            $this->eventsDispatcher->dispatch(new TaskUpdated($task, $usersSyncResult));
        });
    }

    public function deleteTask(Task $task): void
    {
        $this->connection->transaction(function () use ($task) {
            $task->delete();

            $this->touchRelated($task);
        });

        $this->eventsDispatcher->dispatch(new TaskDeleted($task));
    }

    public function updateReminder(TaskReminder $reminder, SetTaskReminderData $data): TaskReminder
    {
        return \tap($reminder, function (TaskReminder $reminder) use ($data): void {
            $reminder->forceFill($data->all());

            $this->connection->transaction(static function () use ($reminder): void {
                $reminder->save();
            });
        });
    }

    public function deleteReminder(TaskReminder $reminder): void
    {
        $this->connection->transaction(static function () use ($reminder): void {
            $reminder->delete();
        });
    }

    protected function touchRelated(Task $task): void
    {
        foreach ($task->linkedModelRelations as $relation) {
            /* @var \App\Domain\Note\Models\ModelHasNotes $relation */
            $relation->related?->touch();
        }
    }

    public function setCauser(?Model $causer): static
    {
        return \tap($this, fn () => $this->causer = $causer);
    }
}
