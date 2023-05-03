<?php

namespace App\Domain\Task\Listeners;

use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskExpired;
use App\Domain\Task\Events\TaskUpdated;
use App\Domain\Task\Notifications\InvitedToTaskNotification;
use App\Domain\Task\Notifications\RevokedInvitationFromTaskNotification;
use App\Domain\Task\Notifications\TaskCreatedNotification;
use App\Domain\Task\Notifications\TaskDeletedNotification;
use App\Domain\Task\Notifications\TaskExpiredNotification;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;

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

        $task->user->notify(new TaskCreatedNotification($task));

        if ($task->users->isNotEmpty()) {
            Notification::send($task->users, new InvitedToTaskNotification($task));
        }
    }

    public function handleUpdatedEvent(TaskUpdated $event): void
    {
        $task = $event->task;

        $syncedUsers = $event->usersSyncResult;

        $invitedUsers = User::query()->whereKey(Arr::get($syncedUsers, 'attached', []))->get();
        $revokedUsers = User::query()->whereKey(Arr::get($syncedUsers, 'detached', []))->get();

        if ($invitedUsers->isNotEmpty()) {
            Notification::send($invitedUsers, new InvitedToTaskNotification($task));
        }

        if ($revokedUsers->isNotEmpty()) {
            Notification::send($revokedUsers, new RevokedInvitationFromTaskNotification($task));
        }
    }

    public function handleDeletedEvent(TaskDeleted $event): void
    {
        $task = $event->task;

        $task->user->notify(new TaskDeletedNotification($task));

        if ($task->users->isNotEmpty()) {
            Notification::send($task->users, new TaskDeletedNotification($task));
        }
    }

    public function handleExpiredEvent(TaskExpired $event): void
    {
        $task = $event->task;

        $users = $task->users;

        if (null !== $task->user) {
            $users = $users->merge([$task->user]);
        }

        Notification::send($users, new TaskExpiredNotification($task));
    }
}
