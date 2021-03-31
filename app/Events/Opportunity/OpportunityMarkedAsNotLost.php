<?php

namespace App\Events\Opportunity;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityMarkedAsNotLost
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private \App\Models\Opportunity $opportunity;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Opportunity $opportunity
     */
    public function __construct(\App\Models\Opportunity $opportunity)
    {
        //
        $this->opportunity = $opportunity;
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
