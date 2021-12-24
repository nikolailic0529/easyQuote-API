<?php

namespace App\Events\Team;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private \App\Models\Team $team;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Team $team
     */
    public function __construct(\App\Models\Team $team)
    {
        //
        $this->team = $team;
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
