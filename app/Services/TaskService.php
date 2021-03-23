<?php

namespace App\Services;

use App\DTO\Tasks\CreateTaskData;
use App\DTO\Tasks\UpdateTaskData;
use App\Events\Task\TaskCreated;
use App\Events\Task\TaskDeleted;
use App\Events\Task\TaskUpdated;
use App\Services\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Models\{
    Task,
    Quote\Quote
};

class TaskService
{
    protected ConnectionInterface $connection;

    protected ValidatorInterface $validator;

    protected Dispatcher $eventsDispatcher;

    public function __construct(ConnectionInterface $connection, ValidatorInterface $validator, Dispatcher $eventsDispatcher)
    {
        $this->connection = $connection;
        $this->validator = $validator;
        $this->eventsDispatcher = $eventsDispatcher;
    }

    public function createTaskForTaskable(CreateTaskData $data, Model $taskable): Task
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new Task(), function (Task $task) use ($data, $taskable) {
            $task->taskable_id = $taskable->getKey();
            $task->taskable_type = $taskable->getMorphClass();
            $task->name = $data->name;
            $task->content = $data->content;
            $task->expiry_date = $data->expiry_date;
            $task->priority = $data->priority;

            $this->connection->transaction(function () use ($data, $task) {
               $task->save();

               $task->users()->sync($data->users);
               $task->attachments()->sync($data->attachments);
            });

            $this->eventsDispatcher->dispatch(new TaskCreated($task));
        });
    }

    public function updateTask(Task $task, UpdateTaskData $data): Task
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($task, function (Task $task) use ($data) {
            $usersSyncResult = [];

            $this->connection->transaction(function () use ($data, $task, &$usersSyncResult) {
                $this->handleTaskExpiry($task, $data->expiry_date);

                $task->name = $data->name;
                $task->content = $data->content;
                $task->expiry_date = $data->expiry_date;
                $task->priority = $data->priority;

                $task->save();

                $usersSyncResult = $task->users()->sync($data->users);
                $task->attachments()->sync($data->attachments);
            });

            $this->eventsDispatcher->dispatch(new TaskUpdated($task, $usersSyncResult));
        });
    }

    protected function handleTaskExpiry(Task $task, Carbon $expiryDate): void
    {
        if ($expiryDate->ne($task->expiry_date)) {
            $task->notifications()->where('notification_key', 'expired')->delete();
        }
    }

    public function deleteTask(Task $task): void
    {
        $this->connection->transaction(fn() => $task->delete());

        $this->eventsDispatcher->dispatch(new TaskDeleted($task));
    }

    public function qualifyTaskableModel(Task $task): string
    {
        return class_basename($task->taskable);
    }

    public function qualifyTaskableUnique(Task $task): ?string
    {
        if ($task->taskable instanceof Quote) {
            return sprintf('RFQ %s', $task->taskable->customer->rfq);
        }

        return null;
    }

    public function qualifyTaskableRoute(Task $task): ?string
    {
        if ($task->taskable instanceof Quote) {
            return ui_route('quotes.status', ['quote' => $task->taskable]);
        }

        return null;
    }

    public function qualifyTaskableName(Task $task): string
    {
        return implode(' ', [$this->qualifyTaskableModel($task), $this->qualifyTaskableUnique($task)]);
    }
}
