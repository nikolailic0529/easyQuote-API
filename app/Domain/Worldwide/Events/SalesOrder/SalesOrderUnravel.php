<?php

namespace App\Domain\Worldwide\Events\SalesOrder;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderUnravel
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private \App\Domain\Worldwide\Models\SalesOrder $salesOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Domain\Worldwide\Models\SalesOrder $salesOrder)
    {
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
