<?php

namespace App\Events\Task;

use App\Models\{
    Task,
    User,
};
use App\Services\TaskService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TaskExpired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        /** Creator and assigned users. */
        $users = (clone $this->task->users)->push($this->task->user);

        return $users
            ->map(fn (User $user) => new PrivateChannel('user.' . $user->getKey()))
            ->toArray();
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'task.expired';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $service = app(TaskService::class);
        
        $message = sprintf(
            'Task "%s" by %s has been expired.',
            $this->task->name,
            $service->qualifyTaskableName($this->task)
        );

        return [
            'id' => (string) Str::uuid(4),
            'message' => $message,
            'created_at' => now()->format(config('date.format_time')),
        ];
    }
}
