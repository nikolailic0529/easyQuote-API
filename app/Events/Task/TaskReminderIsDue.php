<?php

namespace App\Events\Task;

use App\Models\Attachment;
use App\Models\Task\TaskReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

final class TaskReminderIsDue implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly TaskReminder $reminder
    ) {
    }

    public function broadcastOn(): array
    {
        $user = $this->reminder->user;

        if (null === $user) {
            return [];
        }

        return [new PrivateChannel("user.{$user->getKey()}")];
    }

    public function broadcastAs(): string
    {
        return 'task.reminder';
    }

    public function broadcastWith(): array
    {
        $user = $this->reminder->user;

        $tz = $user->timezone->utc ?? config('app.timezone');

        $task = $this->reminder->task;

        return [
            'id' => $task->getKey(),
            'reminder_id' => $this->reminder->getKey(),
            'activity_type' => $task->activity_type,
            'name' => $task->name,
            'content' => $task->content,
            'expiry_date' => $task->expiry_date?->tz($tz)?->format(config('date.format_time')),
            'priority' => $task->priority,
        ];
    }
}
