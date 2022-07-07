<?php

namespace App\Events\Task;

use App\Models\{Task\Task, User,};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TaskExpired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
            ->map(static fn(User $user) => new PrivateChannel('user.'.$user->getKey()))
            ->all();
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'task.expired';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $message = sprintf(
            'Task has been expired: `%s`',
            $this->task->name,
        );

        return [
            'id' => (string)Str::uuid(4),
            'message' => $message,
            'created_at' => now()->format(config('date.format_time')),
        ];
    }
}
