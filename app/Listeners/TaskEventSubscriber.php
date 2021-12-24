<?php

namespace App\Listeners;

use App\Contracts\Multitenantable;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Events\Task\{
    TaskCreated,
    TaskDeleted,
    TaskExpired,
    TaskUpdated,
};
use App\Models\User;
use App\Notifications\Task\{
    TaskCreated as CreatedNotification,
    TaskAssigned as AssignedNotification,
    TaskDeleted as DeletedNotification,
    TaskRevoked as RevokedNotification,
    TaskExpired as ExpiredNotification
};
use App\Services\TaskService;
use Arr;

class TaskEventSubscriber
{
    protected Users $users;

    protected TaskService $service;

    public function __construct(Users $users, TaskService $service)
    {
        $this->users = $users;
        $this->service = $service;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(TaskCreated::class, self::class . '@handleCreatedEvent');
        $events->listen(TaskUpdated::class, self::class . '@handleUpdatedEvent');
        $events->listen(TaskDeleted::class, self::class . '@handleDeletedEvent');
        $events->listen(TaskExpired::class, self::class . '@handleExpiredEvent');
    }

    public function handleCreatedEvent(TaskCreated $event)
    {
        $task = $event->task;
        $taskable = $task->taskable;
        $taskableRoute = $this->service->qualifyTaskableRoute($task);
        $taskableName = $this->service->qualifyTaskableName($task);

        /**
         * Notify taskable owner that task has been created.
         */
        if ($taskable instanceof Multitenantable) {
            $message = sprintf(
                'The new task "%s" created by %s',
                $task->name,
                $taskableName
            );

            notification()
                ->for($taskable->user)
                ->message($message)
                ->subject($taskable)
                ->url($taskableRoute)
                ->priority(1)
                ->store();

            $taskable->user->notify(new CreatedNotification($task, $taskableName, $taskableRoute));
        }

        /**
         * Notify assigned users about the task.
         */
        $task->users->each(function (User $user) use ($task, $taskableName, $taskableRoute) {
            $message = sprintf(
                'User %s assigned you to the new task "%s" by %s',
                $task->user->email,
                $task->name,
                $taskableName
            );

            $notification = notification()
                ->for($user)
                ->message($message)
                ->subject($task->taskable)
                ->priority(2);

            transform($taskableRoute, function (string $taskableRoute) use ($notification) {
                $notification->url($taskableRoute);
            });

            $notification->store();

            $user->notify(new AssignedNotification($task, $taskableName, $taskableRoute));
        });
    }

    public function handleUpdatedEvent(TaskUpdated $event)
    {
        $task = $event->task;
        $taskableRoute = $this->service->qualifyTaskableRoute($task);
        $taskableName = $this->service->qualifyTaskableName($task);

        $syncedUsers = $event->usersSyncResult;

        $attachedUsers = $this->users->findMany(Arr::get($syncedUsers, 'attached', []));
        $detachedUsers = $this->users->findMany(Arr::get($syncedUsers, 'detached', []));

        /**
         * Notify task assigned users about the quote task.
         */
        $attachedUsers->each(function (User $user) use ($task, $taskableName, $taskableRoute) {
            $message = sprintf(
                'User %s assigned you to the task "%s" by %s',
                $task->user->email,
                $task->name,
                $taskableName
            );

            $notification = notification()
                ->for($user)
                ->message($message)
                ->subject($task->taskable)
                ->priority(2);

            transform($taskableRoute, function (string $taskableRoute) use ($notification) {
                $notification->url($taskableRoute);
            });

            $notification->store();

            $user->notify(new AssignedNotification($task, $taskableName, $taskableRoute));
        });

        $detachedUsers->each(function (User $user) use ($task, $taskableName) {
            $message = sprintf(
                'User %s revoked you from the task "%s" by %s',
                $task->user->email,
                $task->name,
                $taskableName
            );

            notification()
                ->for($user)
                ->message($message)
                ->subject($task->taskable)
                ->priority(2)
                ->store();

            $user->notify(new RevokedNotification($task, $taskableName));
        });
    }

    public function handleDeletedEvent(TaskDeleted $event)
    {
        $task = $event->task;
        $taskable = $task->taskable;
        $taskableRoute = $this->service->qualifyTaskableRoute($task);
        $taskableName = $this->service->qualifyTaskableName($task);

        /**
         * Notify taskable owner that task has been deleted.
         */
        if ($taskable instanceof Multitenantable) {
            $message = sprintf(
                'The task "%s" by %s has been deleted',
                $task->name,
                $taskableName
            );

            notification()
                ->for($taskable->user)
                ->message($message)
                ->subject($taskable)
                ->url($taskableRoute)
                ->priority(1)
                ->store();

            $taskable->user->notify(new DeletedNotification($task, $taskableName));
        }

        /**
         * Notify task assigned users that task deleted.
         */
        $task->users->each(function (User $user) use ($task, $taskableName) {
            $message = sprintf(
                'The task "%s" you were assigned by %s has been deleted',
                $task->name,
                $taskableName
            );

            notification()
                ->for($user)
                ->message($message)
                ->subject($task->taskable)
                ->priority(2)
                ->store();

            $user->notify(new DeletedNotification($task, $taskableName));
        });
    }

    public function handleExpiredEvent(TaskExpired $event)
    {
        $task = $event->task;
        $taskableRoute = $this->service->qualifyTaskableRoute($task);
        $taskableName = $this->service->qualifyTaskableName($task);

        /**
         * Notifiy task creator and task assigned users.
         */
        $users = (clone $task->users)->push($task->user);

        $users->each(function (User $user) use ($task, $taskableName, $taskableRoute) {
            $message = sprintf(
                'Task "%s" by %s has been expired.',
                $task->name,
                $taskableName
            );

            notification()
                ->for($user)
                ->message($message)
                ->url($taskableRoute)
                ->subject($task->taskable)
                ->priority(3)
                ->store();

            $user->notify(new ExpiredNotification($task, $taskableName, $taskableRoute));
        });
    }
}
