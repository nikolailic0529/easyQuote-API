<?php

namespace App\Listeners;

use App\Events\Task\{TaskCreated, TaskDeleted, TaskExpired, TaskUpdated,};
use App\Models\User;
use App\Notifications\Task\{InvitedToTaskNotification,
    RevokedInvitationFromTaskNotification,
    TaskCreatedNotification,
    TaskDeletedNotification,
    TaskExpiredNotification};
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class TaskEventSubscriber
{
    public function __construct()
    {
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(TaskCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(TaskUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(TaskDeleted::class, [self::class, 'handleDeletedEvent']);
        $events->listen(TaskExpired::class, [self::class, 'handleExpiredEvent']);
    }

    public function handleCreatedEvent(TaskCreated $event)
    {
        $task = $event->task;

        $message = sprintf(
            'New task created: `%s`',
            $task->name,
        );

        notification()
            ->for($task->user)
            ->message($message)
            ->subject($task)
            ->priority(1)
            ->push();

        $task->user->notify(new TaskCreatedNotification($task));

        /**
         * Notify assigned users about the task.
         */
        $task->users->each(function (User $user) use ($task): void {
            $message = sprintf(
                'You have been invited to the task task: `%s`',
                $task->name,
            );

            $notification = notification()
                ->for($user)
                ->message($message)
                ->subject($task)
                ->priority(2);

            $notification->push();

            $user->notify(new InvitedToTaskNotification($task));
        });
    }

    public function handleUpdatedEvent(TaskUpdated $event)
    {
        $task = $event->task;

        $syncedUsers = $event->usersSyncResult;

        $attachedUsers = User::query()->whereKey(Arr::get($syncedUsers, 'attached', []));
        $detachedUsers = User::query()->whereKey(Arr::get($syncedUsers, 'detached', []));

        /**
         * Notify task assigned users about the quote task.
         */
        $attachedUsers->each(function (User $user) use ($task): void {
            $message = sprintf(
                'You have been invited to the task task: `%s`',
                $task->name,
            );

            $notification = notification()
                ->for($user)
                ->message($message)
                ->subject($task)
                ->priority(2);

            $notification->push();

            $user->notify(new InvitedToTaskNotification($task));
        });

        $detachedUsers->each(function (User $user) use ($task): void {
            $message = sprintf(
                'Your invitation has been revoked from task: `%s`',
                $task->name,
            );

            notification()
                ->for($user)
                ->message($message)
                ->subject($task)
                ->priority(2)
                ->push();

            $user->notify(new RevokedInvitationFromTaskNotification($task));
        });
    }

    public function handleDeletedEvent(TaskDeleted $event)
    {
        $task = $event->task;

        $message = sprintf(
            'Task has been deleted: `%s`',
            $task->name,
        );

        notification()
            ->for($task->user)
            ->message($message)
            ->subject($task)
            ->priority(1)
            ->push();

        $task->user->notify(new TaskDeletedNotification($task));

        /**
         * Notify task assigned users that task deleted.
         */
        $task->users->each(function (User $user) use ($task) {
            $message = sprintf(
                'Task you were assigned to has been deleted: `%s`',
                $task->name,
            );

            notification()
                ->for($user)
                ->message($message)
                ->subject($task)
                ->priority(2)
                ->push();

            $user->notify(new TaskDeletedNotification($task));
        });
    }

    public function handleExpiredEvent(TaskExpired $event)
    {
        $task = $event->task;

        /**
         * Notify task creator and task assigned users.
         */
        $users = $task->users;

        if (null !== $task->user) {
            $users = $users->merge([$task->user]);
        }

        $users->each(function (User $user) use ($task): void {
            $message = sprintf(
                'Task has been expired: `%s`',
                $task->name,
            );

            notification()
                ->for($user)
                ->message($message)
                ->subject($task)
                ->priority(3)
                ->push();

            $user->notify(new TaskExpiredNotification($task));
        });
    }
}
