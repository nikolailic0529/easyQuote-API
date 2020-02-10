<?php

namespace App\Events;

use App\Models\System\Notification;
use Illuminate\Broadcasting\{
    InteractsWithSockets,
    PrivateChannel
};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var \App\Models\System\Notification */
    protected $notification;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->notification->user_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'notification.read';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id'        => $this->notification->id,
            'decrement' => true
        ];
    }
}
