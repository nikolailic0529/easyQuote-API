<?php

namespace App\Events\SalesOrder;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private \App\Models\SalesOrder $salesOrder;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\SalesOrder $salesOrder
     */
    public function __construct(\App\Models\SalesOrder $salesOrder)
    {
        //
        $this->salesOrder = $salesOrder;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
