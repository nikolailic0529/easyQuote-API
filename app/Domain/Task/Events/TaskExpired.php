<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Models\Task;
use App\Domain\User\Models\{User};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TaskExpired implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public readonly Task $task)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        /** Creator and assigned users. */
        $users = $this->task->users;

        if (null !== $this->task->user) {
            $users = $users->merge([$this->task->user]);
        }

        return $users
            ->map(static fn (User $user) => new PrivateChannel('user.'.$user->getKey()))
            ->all();
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.expired';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $message = sprintf(
            '%s [%s] has been expired',
            $this->task->activity_type->value,
            $this->task->name,
        );

        return [
            'id' => (string) Str::uuid(4),
            'message' => $message,
            'created_at' => now()->format(config('date.format_time')),
        ];
    }
}
