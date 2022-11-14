<?php

namespace App\Listeners;

use App\Events\Task\TaskCreated;
use App\Events\Task\TaskDeleted;
use App\Events\Task\TaskExpired;
use App\Events\Task\TaskUpdated;
use App\Models\User;
use App\Notifications\Task\InvitedToTaskNotification;
use App\Notifications\Task\RevokedInvitationFromTaskNotification;
use App\Notifications\Task\TaskCreatedNotification;
use App\Notifications\Task\TaskDeletedNotification;
use App\Notifications\Task\TaskExpiredNotification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class TaskEventSubscriber implements ShouldQueue
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(TaskCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(TaskUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(TaskDeleted::class, [self::class, 'handleDeletedEvent']);
        $events->listen(TaskExpired::class, [self::class, 'handleExpiredEvent']);
    }

    public function handleCreatedEvent(TaskCreated $event): void
    {
        $task = $event->task;

        $message = sprintf(
            'New %s [%s] created',
            $task->activity_type->value,
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
                'You have been invited to the %s [%s]',
                $task->activity_type->value,
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

    public function handleUpdatedEvent(TaskUpdated $event): void
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
                'You have been invited to the %s [%s]',
                $task->activity_type->value,
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
                'Your invitation has been revoked from the %s [%s]',
                $task->activity_type->value,
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

    public function handleDeletedEvent(TaskDeleted $event): void
    {
        $task = $event->task;

        $message = sprintf(
            '%s [%s] deleted',
            $task->activity_type->value,
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
                '%s [%s] deleted',
                $task->activity_type->value,
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

    public function handleExpiredEvent(TaskExpired $event): void
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
                '%s [%s] has been expired',
                $task->activity_type->value,
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
