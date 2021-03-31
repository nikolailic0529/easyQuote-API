<?php

namespace App\Events\WorldwideQuote;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorldwideQuoteMarkedAsDead
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private \App\Models\Quote\WorldwideQuote $quote;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Quote\WorldwideQuote $quote
     */
    public function __construct(\App\Models\Quote\WorldwideQuote $quote)
    {
        //
        $this->quote = $quote;
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
