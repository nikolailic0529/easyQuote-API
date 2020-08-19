<?php

namespace App\Events;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MaintenanceScheduled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @var \App\Models\User */
    protected User $user;

    /** @var \Carbon\Carbon */
    public Carbon $schedule;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Carbon $schedule)
    {
        $this->user = $user;
        $this->schedule = $schedule;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'maintenance.scheduled';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'time' => (string) $this->schedule->toISOString()
        ];
    }
}
